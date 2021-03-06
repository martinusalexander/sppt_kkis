<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

use App\EmailSendRecord;
use App\EmailSendSchedule;
use App\OfflineDistribution;
use App\User;
use App\UserActivityTracking;

use App\Mail\ActivateAccount;
use App\Mail\InformNewAnnouncementRequest;
use App\Mail\PasswordChanged;
use App\Mail\ResetPassword;
use App\Mail\ShareOfflineDistribution;


class EmailController extends Controller
{
    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the list of media
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        // Track user activity
        UserActivityTracking::create([
            'user_id' => Auth::id(),
            'activity_type' => 'DISPLAY',
            'activity_details' => 'EMAIL_SEND_SCHEDULE_INDEX',
            'full_url' => $request->fullUrl(),
            'method' => $request->method(),
            'is_ajax' => $request->ajax(),
            'is_secure' => $request->secure(),
            'ip' => $request->ip(),
            'header' => json_encode($request->header()),
        ]);

        // Non-admin cannot perform this action
        $user = Auth::user();
        if (!$user->is_admin) {
            abort(403);
        }

        $last_month = Carbon::now()->subMonth();
        $last_month_timestamp = $last_month->timestamp;

        $schedules = EmailSendSchedule
            ::where('send_timestamp', '>=', $last_month_timestamp)
            ->orWhere('status', '!=', 'SUCCESS')
            ->orderBy('send_timestamp', 'desc')
            ->get();
        foreach ($schedules as $schedule) {
            $schedule->send_datetime =
                Carbon::createFromTimestamp($schedule->send_timestamp)->format('l, j F Y, g:i a');
            $schedule->request_parameter =
                json_encode(json_decode($schedule->request_parameter), JSON_PRETTY_PRINT);
        }
        return view('emailsendschedule.index', ['email_send_schedules' => $schedules]);
    }

    /**
     * Display the view email send schedule page
     *
     * @param Request $request
     * @param string $announcement_id
     * @return Response
     */
    public function view(Request $request, string $email_send_schedule_id)
    {
        // Track user activity
        UserActivityTracking::create([
            'user_id' => Auth::id(),
            'activity_type' => 'DISPLAY',
            'activity_details' => 'EMAIL_SEND_SCHEDULE_VIEW',
            'full_url' => $request->fullUrl(),
            'method' => $request->method(),
            'is_ajax' => $request->ajax(),
            'is_secure' => $request->secure(),
            'ip' => $request->ip(),
            'header' => json_encode($request->header()),
        ]);

        // Non-admin cannot perform this action
        $user = Auth::user();
        if (!$user->is_admin) {
            abort(403);
        }

        $email_send_schedule = EmailSendSchedule::findOrFail($email_send_schedule_id);

        // Convert some schedule details into the frontend format
        $email_send_schedule->request_parameter =
            json_encode(json_decode($email_send_schedule->request_parameter), JSON_PRETTY_PRINT);
        $email_send_schedule->send_datetime =
            Carbon::createFromTimestamp($email_send_schedule->send_timestamp)->format('l, j F Y, g:i a');

        // Retrieve the records
        $email_send_records = $email_send_schedule
            ->email_send_record()
            ->orderBy('create_timestamp')
            ->get();
        foreach ($email_send_records as $record) {
            $record->request_parameter =
                json_encode(json_decode($record->request_parameter), JSON_PRETTY_PRINT);
            $record->create_datetime = $record->create_timestamp->format('l, j F Y, g:i a');
        }

        return view('emailsendschedule.view', [
            'email_send_schedule' => $email_send_schedule,
            'email_send_records' => $email_send_records
        ]);
    }

    /**
     * Run email sending jobs automatically
     *
     * @return void
     */
    public function __invoke()
    {
        $this->run();
        return;
    }

    /**
     * Run email sending jobs manually
     *
     * @return void
     */
    public function manual_invoke(Request $request, string $email_send_schedule_id)
    {
        // Track user activity
        UserActivityTracking::create([
            'user_id' => Auth::id(),
            'activity_type' => 'CLICK',
            'activity_details' => 'EMAIL_SEND_SCHEDULE_MANUAL_INVOKE',
            'full_url' => $request->fullUrl(),
            'method' => $request->method(),
            'is_ajax' => $request->ajax(),
            'is_secure' => $request->secure(),
            'ip' => $request->ip(),
            'header' => json_encode($request->header()),
        ]);

        // Non-admin cannot perform this action
        $user = Auth::user();
        if (!$user->is_admin) {
            abort(403);
        }

        $this->run($email_send_schedule_id, true);
        return back(303)->with('success_message', 'Task pengiriman email telah berhasil dieksekusi.');
    }

    /**
     * Execute email sending jobs
     *
     * @return void
     */
    private function run(string $email_send_schedule_id = null, bool $is_manual = false)
    {
        if ($email_send_schedule_id !== null) {
            $schedules = EmailSendSchedule
                ::where('id', $email_send_schedule_id)
                ->get();
        } else {
            $now = Carbon::now();
            $current_timestamp = $now->timestamp;
            $schedules = EmailSendSchedule
                ::where('status', '!=', 'SUCCESS')
                ->where('send_timestamp', '<=', $current_timestamp)
                ->get();
        }

        foreach ($schedules as $schedule) {
            // First, mark as ongoing
            $schedule->update(['status' => 'ON_PROGRESS']);

            $email_send_record_array = array(
                'email_send_schedule_id' => $schedule->id,
                'request_parameter' => $schedule->request_parameter
            );
            if ($is_manual) {
                $email_send_record_array['is_manual'] = $is_manual;
                $email_send_record_array['creator_id'] = Auth::id();
            }
            $record = EmailSendRecord::create($email_send_record_array);

            $request_parameter = json_decode($schedule->request_parameter, true);
            // Always bcc
            $request_parameter['bcc'] = array(config('mail.from.address'));


            try {
                switch ($schedule->email_class) {
                    case 'ActivateAccount':
                        $user_id = $request_parameter['user_id'];
                        $user = User::findOrFail($user_id);
                        $request_parameter['user'] = $user->toJson();

                        $record->update(['request_parameter' => json_encode($request_parameter)]);

                        Mail::to($request_parameter['to'])
                            ->bcc($request_parameter['bcc'])
                            ->send(new ActivateAccount($user, $request_parameter['token']));

                        break;
                    case 'PasswordChanged':
                        $user_id = $request_parameter['user_id'];
                        $user = User::findOrFail($user_id);
                        $request_parameter['user'] = $user->toJson();

                        $record->update(['request_parameter' => json_encode($request_parameter)]);

                        Mail::to($request_parameter['to'])
                            ->bcc($request_parameter['bcc'])
                            ->send(new PasswordChanged($user));

                        break;
                    case 'ResetPassword':
                        $user_id = $request_parameter['user_id'];
                        $user = User::findOrFail($user_id);
                        $request_parameter['user'] = $user->toJson();

                        $record->update(['request_parameter' => json_encode($request_parameter)]);

                        Mail::to($request_parameter['to'])
                            ->bcc($request_parameter['bcc'])
                            ->send(new ResetPassword($user, $request_parameter['token'], $request_parameter['create_time']));

                        break;
                    case 'ShareOfflineDistribution':
                        $offline_distribution_id = $request_parameter['offline_distribution_id'];
                        $offline_distribution = OfflineDistribution::findOrFail($offline_distribution_id);
                        $request_parameter['offline_distribution'] = $offline_distribution->toJson();

                        $record->update(['request_parameter' => json_encode($request_parameter)]);

                        Mail::to($request_parameter['to'])
                            ->bcc($request_parameter['bcc'])
                            ->send(new ShareOfflineDistribution($offline_distribution));

                        break;
                    case 'InformNewAnnouncementRequest':
                        Mail::to($request_parameter['to'])
                            ->bcc($request_parameter['bcc'])
                            ->send(new InformNewAnnouncementRequest(
                                json_decode($request_parameter['new_announcement_request_titles'], true),
                                json_decode($request_parameter['revised_announcement_request_titles'], true)
                            ));
                        break;
                    default:
                        throw new Exception('Unknown email class name.');
                }
            } catch (\Exception $e) {
                // If not success, mark as failed.
                $schedule->update(['status' => 'FAILED']);
                $record->update([
                    'status' => 'FAILED',
                    'error' => $e->getMessage()."\n".$e->getTraceAsString()
                ]);
                continue;
            } catch (Exception $e) {
                // If not success, mark as failed.
                $schedule->update(['status' => 'FAILED']);
                $record->update([
                    'status' => 'FAILED',
                    'error' => $e->getMessage()."\n".$e->getTraceAsString()
                ]);
                continue;
            }

            // Finally, mark as success.
            $schedule->update(['status' => 'SUCCESS']);
            $record->update(['status' => 'SUCCESS']);
        }
    }
}

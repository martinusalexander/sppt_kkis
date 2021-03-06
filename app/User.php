<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'is_active', 'is_admin', 'is_blocked'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

    const CREATED_AT = 'create_timestamp';

    const UPDATED_AT = 'update_timestamp';

    /**
     * Get the activation token record that belongs to the user.
     */
    public function account_activation()
    {
        return $this->hasOne('App\AccountActivation');
    }

    /**
     * Get the password record that belongs to the user.
     */
    public function password_reset()
    {
        return $this->hasMany('App\PasswordReset', 'email', 'email');
    }

    /**
     * Get the announcement request record that belongs to the user.
     */
    public function announcement_request()
    {
        return $this->hasMany('App\AnnouncementRequest', 'creator_id');
    }

    /**
     * Get the announcement request record that was edited by the user.
     */
    public function announcement_request_edit()
    {
        return $this->hasMany('App\AnnouncementRequest', 'editor_id');
    }

    /**
     * Get the announcement record that belongs to the user.
     */
    public function announcement()
    {
        return $this->hasMany('App\Announcement', 'creator_id');
    }

    /**
     * Get the announcement record that was edited by the user.
     */
    public function announcement_edit()
    {
        return $this->hasMany('App\Announcement', 'editor_id');
    }

    /**
     * Get the activity tracking record that was performed by the user.
     */
    public function user_activity_tracking()
    {
        return $this->hasMany('App\UserActivityTracking');
    }
}

<?php

namespace Illuminate\Foundation\Auth;

trait RedirectsUsers
{
    /**
     * Get the post register / login redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/home';
    }

    /**
     * Get the post logout redirect path.
     *
     * @return string
     */
    public function redirectAfterLogoutPath()
    {
        return property_exists($this, 'redirectAfterLogout') ? $this->redirectAfterLogout : '/';
    }
}

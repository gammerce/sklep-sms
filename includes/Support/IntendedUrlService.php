<?php
namespace App\Support;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class IntendedUrlService
{
    const URL_INTENDED_KEY = "url.intended";

    public function set(Request $request)
    {
        $session = $request->getSession();

        if ($session) {
            $session->set(static::URL_INTENDED_KEY, $request->getRequestUri());
        }
    }

    public function exists(Request $request)
    {
        $session = $request->getSession();
        return $session && $session->has(static::URL_INTENDED_KEY);
    }

    public function remove(Request $request)
    {
        $session = $request->getSession();
        if ($session) {
            $session->remove(static::URL_INTENDED_KEY);
        }
    }

    public function get(Request $request)
    {
        $session = $request->getSession();

        if ($session) {
            return $session->get(static::URL_INTENDED_KEY);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws UnexpectedValueException
     */
    public function redirect(Request $request)
    {
        $intendedUrl = $this->get($request);
        $this->remove($request);

        if (!$intendedUrl) {
            throw new UnexpectedValueException("Intended url doesn't exist");
        }

        return new RedirectResponse($intendedUrl);
    }
}

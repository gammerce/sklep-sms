<?php
namespace App\View\Blocks;

use App\Support\Template;
use App\System\Auth;
use Exception;

abstract class BlockSimple extends Block
{
    protected $template = null;

    public function __construct()
    {
        if (!isset($this->template)) {
            $className = get_class($this);
            throw new Exception(
                "Class $className has to have field \$template because it extends class BlockSimple"
            );
        }
    }

    protected function content(array $query, array $body, array $params)
    {
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);
        $user = $auth->user();

        /** @var Template $template */
        $template = app()->make(Template::class);

        return $template->render($this->template, compact('user'));
    }
}

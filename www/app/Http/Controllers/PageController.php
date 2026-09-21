<?php

namespace App\Http\Controllers;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function __invoke(Page $page): View
    {
        abort_unless($page->status === PageStatus::Published, 404);

        return view('page', ['page' => $page]);
    }
}

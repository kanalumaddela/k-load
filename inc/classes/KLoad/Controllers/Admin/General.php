<?php
/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2021 kanalumaddela
 * @license   MIT
 */

namespace KLoad\Controllers\Admin;

use KLoad\Controllers\AdminController;
use KLoad\Exceptions\InvalidToken;
use KLoad\Facades\Lang;
use KLoad\Helpers\Util;
use KLoad\Http\RedirectResponse;
use KLoad\Models\Setting;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use function KLoad\flash;
use function KLoad\redirect;
use function pathinfo;
use function str_contains;
use function uniqid;
use const KLoad\APP_ROOT;
use const KLoad\APP_ROUTE_URL;

class General extends AdminController
{
    protected static string $templateFolder = 'general';

    public function general(): Response
    {
        $this->authorize('general');

        $settings = Setting::whereIn('name', ['community_name', 'description', 'logo'])->get()->pluck('value', 'name');
        $logos = Util::listDir(APP_ROOT . '/assets/img/logos');

        return $this->view('general', get_defined_vars());
    }

    public function generalPost(): RedirectResponse
    {
        $this->validateCsrf();
        $this->authorize('general');

        $post = $this->getPost();

        if ($name = $post->get('community_name')) {
            Setting::where('name', 'community_name')->update(['value' => $name]);
            flash('success', Lang::get('community_name_updated', 'Community name has been updated'));
        }

        if ($post->has('description')) {
            Setting::where('name', 'description')->update(['value' => $post->get('description')]);
            flash('success', Lang::get('description_updated', 'Description name has been updated'));
        }

        return redirect(APP_ROUTE_URL . '/dashboard/admin/general');
    }

    public function logo(): RedirectResponse
    {
        $this->validateCsrf();
        $this->authorize('general');

        $post = $this->getPost();

        if (file_exists(APP_ROOT . '/assets/img/logos/' . ($logo = $post->get('logo')))) {
            $logoSetting = Setting::find('logo');

            if ($post->get('action') === 'delete') {
                unlink(APP_ROOT . '/assets/img/logos/' . $logo);
                $logoSetting->value = '';
                $logoSetting->save();

                flash('success', Lang::get('logo_deleted', 'Logo has been deleted'));
            } else {
                $logoSetting->value = $logo;
                $logoSetting->save();
                flash('success', Lang::get('logo_updated', 'Logo has been updated'));
            }

        }

        return redirect(APP_ROUTE_URL . '/dashboard/admin/general');
    }

    /**
     * @throws InvalidToken
     */
    public function logoUpload(): RedirectResponse
    {
        $this->validateCsrf();
        $this->authorize('general');

        /** @var UploadedFile $logo */
        foreach ($this->request->files->get('logo-files') as $logo) {
            if (!str_contains($logo->getMimeType(), 'image/')) {
                continue;
            }

            $name = pathinfo($logo->getClientOriginalName(), PATHINFO_FILENAME);
            $sanitized = strtolower(preg_replace('/[[:^print:]]/', '', $name));
            $newName = $sanitized . '.' . uniqid('', true) . '.' . $logo->guessExtension();

            try {
                $ext = $logo->guessExtension();

                $logo->move(APP_ROOT . '/assets/img/logos', $newName);

                flash('success', 'Uploaded `' . $name . '.' . $ext . '`');
            } catch (FileException $e) {
                flash('error', 'Failed to upload logo: ' . $e->getMessage());
//                flash('error', Lang::get('failed_to_upload_logo', [$name]));
            }
        }

        return redirect(APP_ROUTE_URL . '/dashboard/admin/general');
    }
}

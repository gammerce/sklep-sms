<?php
namespace Tests\Feature\Http\Api\Admin;

use App\System\Settings;
use Tests\Psr4\TestCases\HttpTestCase;

class SettingsControllerTest extends HttpTestCase
{
    /** @test */
    public function updates_settings()
    {
        // given
        $admin = $this->factory->admin();
        $this->actingAs($admin);

        // when
        $response = $this->put("/api/admin/settings", [
            'shop_url' => "https://example.com",
            'license_token' => "abc123",
            'vat' => 1.23,
            'row_limit' => 20,
            'delete_logs' => 1,
            'cron' => 1,
            'user_edit_service' => 1,
            'theme' => 'default',
            'language' => 'polish',
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);
        $settings->load();
        $this->assertSame("https://example.com", $settings["shop_url"]);
        $this->assertSame("abc123", $settings->getLicenseToken());
        $this->assertSame("1.23", $settings->getVat());
        $this->assertSame("20", $settings["row_limit"]);
        $this->assertSame("1", $settings["delete_logs"]);
        $this->assertSame("1", $settings["cron_each_visit"]);
        $this->assertSame("1", $settings["user_edit_service"]);
        $this->assertSame("default", $settings["theme"]);
        $this->assertSame("polish", $settings["language"]);
    }
}

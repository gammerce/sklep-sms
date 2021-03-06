<?php
namespace Tests\Feature\Http\Api\Shop;

use Tests\Psr4\Concerns\MakePurchaseConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class PurchaseResourceTest extends HttpTestCase
{
    use MakePurchaseConcern;

    /** @test */
    public function show_extra_flag_purchase_information()
    {
        // given
        $boughtService = $this->createRandomExtraFlagsPurchase();

        // when
        $response = $this->get("/api/purchases/{$boughtService->getId()}");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringMatchesFormat(
            <<<EOF
Zakupiono prawidłowo usługę: <strong>VIP</strong><br />
<strong>Cena</strong>: 1.23 PLN
<hr />
<strong>Serwer</strong>: %s<br />
<strong>Ilość</strong>: %d dni<br />
<strong>Nick</strong>: example<br />
<strong>Hasło</strong>: anc123<br />
<hr />
<strong>Adres e-mail</strong>: example@abc.pl
<hr />
Wpisz w konsoli: setinfo _ss &quot;anc123&quot;

EOF
            ,
            $response->getContent()
        );
    }

    /** @test */
    public function show_mybb_purchase_information()
    {
        // given
        $boughtService = $this->createRandomMybbPurchase();

        // when
        $response = $this->get("/api/purchases/{$boughtService->getId()}");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringMatchesFormat(
            <<<EOF
Zakupiono prawidłowo usługę: <strong>%s</strong><br />
<strong>Cena</strong>: 1.23 PLN
<hr />
<strong>Użytkownik MyBB</strong>: example (1)<br />
<strong>Ilość</strong>: -1 %s<br />
<hr />
<strong>Adres e-mail</strong>: example@abc.pl
<hr />
Jeżeli chcesz zmienić swoją wyświetlaną grupę, postępuj zgodnie z poniższymi krokami:<br />
Wejdź do panelu użytkownika <i>(na forum)</i> -> Kliknij <b>Grupy użytkowników</b> -> <b>Ustaw jako wyświetlaną grupę</b>

EOF
            ,
            $response->getContent()
        );
    }
}

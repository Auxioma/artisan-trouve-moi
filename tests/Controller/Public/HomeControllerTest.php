<?php

namespace App\Tests\Controller\Public;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/public/home');

        self::assertResponseIsSuccessful();
    }
}

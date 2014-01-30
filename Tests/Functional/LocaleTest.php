<?php

/*
 * Copyright 2014 Thomas Wiener <wiener.thomas@googlemail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace TWI\LocaleBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Locale\Locale;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use TWI\LocaleBundle\Service\LocaleManager;

class DefaultControllerTest extends WebTestCase
{
    public function testMissingLocaleInHome()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://www.domain.de/');

        $this->assertTrue($crawler->filter('html:contains("Redirecting to /de_DE/")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_TEMP);
    }

    public function testMissingLocale()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://www.domain.de/login');

        $this->assertTrue($crawler->filter('html:contains("Redirecting to /de_DE/")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_TEMP);
    }

    public function testInvalidLocaleInHome()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://www.domain.de/xx_XX/');

        $this->assertTrue($crawler->filter('html:contains("Redirecting to /")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_PERM);

        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter('html:contains("Redirecting to /de_DE/")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_TEMP);
    }

    public function testInvalidLocale()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://www.domain.de/xx_XX/login');

        $this->assertTrue($crawler->filter('html:contains("Redirecting to /login")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_PERM);

        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter('html:contains("Redirecting to /de_DE/login")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_TEMP);

        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter('html:contains("Jetzt Anmelden")')->count() == 1);
    }

    public function testUnallowedLocaleInHome()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://www.domain.de/de_CH/');

        $this->assertTrue($crawler->filter('html:contains("Redirecting to /")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_PERM);

        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter('html:contains("Redirecting to /de_DE/")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_TEMP);

    }

    public function testUnallowedLocale()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://www.domain.de/de_CH/login');

        $this->assertTrue($crawler->filter('html:contains("Redirecting to /login")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_PERM);

        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter('html:contains("Redirecting to /de_DE/login")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_TEMP);
    }

    public function testValidLocaleEnglish()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://www.domain.de/en_GB/login');

        $this->assertTrue($crawler->filter('html:contains("Log in")')->count() == 1);
    }

    public function testDefaultLocaleCHRedirect()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://www.domain.ch/');

        $this->assertTrue($crawler->filter('html:contains("Redirecting to /de_CH/")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_TEMP);
    }

    public function testNotIncludedPath()
    {
        $client = static::createClient();
        $apiKey = $client->getContainer()->getParameter('api_key');
        $crawler = $client->request('GET', 'http://www.domain.ch/v3/version?api_key=' . $apiKey);

        $result = @json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(true, isset($result['version']));
        $this->assertEquals(3, $result['version']);
        $this->assertEquals(true, isset($result['revision']));
    }

    public function testNotIncludedPathWithLocale()
    {
        $client = static::createClient();
        $apiKey = $client->getContainer()->getParameter('api_key');
        $crawler = $client->request('GET', 'http://www.domain.ch/de_DE/v3/version?api_key=' . $apiKey);

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testLocaleNotInBrowserDefaultList()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://www.domain.jp/login');

        $this->assertTrue($crawler->filter('html:contains("Redirecting to /jp_JP/")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_TEMP);
    }

    public function testLocaleInBrowserDefaultList()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://www.domain.us/login');

        $this->assertTrue($crawler->filter('html:contains("Redirecting to /en_US/")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_TEMP);
    }

    public function testValidLocaleCookie()
    {
        $client = static::createClient();
        $cookie = new Cookie('pl', 'fr_CH', time() + 3600 * 24 * 7, '/', null, false, false);
        $client->getCookieJar()->set($cookie);
        $crawler = $client->request('GET', 'http://www.domain.ch/login');

        $this->assertTrue($crawler->filter('html:contains("Redirecting to /fr_CH/")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_TEMP);
    }

    public function testInvalidLocaleCookie()
    {
        $client = static::createClient();
        $cookie = new Cookie('pl', 'xxxx', time() + 3600 * 24 * 7, '/', null, false, false);
        $client->getCookieJar()->set($cookie);
        $crawler = $client->request('GET', 'http://www.domain.ch/login');

        $this->assertTrue($crawler->filter('html:contains("Redirecting to /de_CH/login")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_TEMP);
    }

    public function testLocaleChangeOnLoginValidLanguage()
    {
        $client = static::createClient();
        $client = $this->login($client, 'chf@domain.ch');

        $cookie = new Cookie(LocaleManager::COOKIE_LOCALE_NAME, 'fr_CH');
        $client->getCookieJar()->set($cookie);

        $crawler = $client->request('GET', 'http://www.domain.ch/user');

        $this->assertTrue($crawler->filter('html:contains("Redirecting to /fr_CH/user")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_TEMP);

        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter('html:contains("Redirecting to http://www.domain.ch/fr_CH/user/")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), 301);

        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter('html:contains("domain | Parquez-vous sans chercher!")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), 200);
    }

    public function testLocaleChangeOnLoginInvalidLanguage()
    {
        $client = static::createClient();
        $client = $this->login($client, 'chf@domain.ch');

        $cookie = new Cookie(LocaleManager::COOKIE_LOCALE_NAME, 'xxxx');
        $client->getCookieJar()->set($cookie);

        $crawler = $client->request('GET', 'http://www.domain.ch/user');

        $this->assertTrue($crawler->filter('html:contains("Redirecting to /de_CH/user")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), LocaleManager::HTTP_REDIRECT_TEMP);

        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter('html:contains("Redirecting to http://www.domain.ch/de_CH/user/")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), 301);

        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter('html:contains("domain | Parkplatz buchen statt suchen")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), 200);
    }
/*
    public function testLocaleChangeOnProfileLocaleUpdate()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testLocaleChangeOnUrlManipulation()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testLocaleChangeOnUrlManipulationWithInvalidLocale()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
*/
    /*public function testLocaleOnLogout()
    {
        $client = static::createClient();
        $client = $this->login($client, 'chf@domain.ch');

        $cookie = new Cookie(LocaleManager::COOKIE_LOCALE_NAME, 'de_CH');
        $client->getCookieJar()->set($cookie);

        $crawler = $client->request('GET', 'http://www.domain.ch/de_CH/user');

        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter('html:contains("domain | Parkplatz buchen statt suchen")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), 200);

        $crawler = $client->request('GET', 'http://www.domain.ch/de_CH/logout');

        $this->assertTrue($crawler->filter('html:contains("Mit domain können Sie private Parkplätze günstig nutzen")')->count() == 1);
        $this->assertEquals($client->getResponse()->getStatusCode(), 200);
    }*/

    protected function logIn($client, $username)
    {
        $em = $client->getContainer()->get('doctrine')->getManager();
        $userObject = $em->getRepository('domainAppBundle:User')->findOneBy(array('email' => $username));

        $session = static::$kernel->getContainer()->get('session');
        $firewall = 'app_area';
        $token = new UsernamePasswordToken($userObject, $userObject->getPassword(), $firewall, array('ROLE_USER'));
        $session->set('_security_' . $firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);

        return $client;
    }

}

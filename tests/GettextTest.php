<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterGettext {
    function gettext(string $message): string
    {
        return $GLOBALS['gettext_stub'][$message] ?? $message;
    }

    function ngettext(string $singular, string $plural, int $n): string
    {
        $key = ($n === 1) ? $singular : $plural;

        return $GLOBALS['ngettext_stub'][$key] ?? $key;
    }

    function dgettext(string $domain, string $message): string
    {
        return $GLOBALS['dgettext_stub'][$domain][$message] ?? $message;
    }

    function dngettext(string $domain, string $singular, string $plural, int $n): string
    {
        $key = ($n === 1) ? $singular : $plural;

        return $GLOBALS['dngettext_stub'][$domain][$key] ?? $key;
    }
}

namespace Tests {
    use CodeIgniter\Test\CIUnitTestCase;
    use Config\App;
    use Michalsn\CodeIgniterGettext\Config\Gettext as GettextConfig;
    use Michalsn\CodeIgniterGettext\Gettext;

    /**
     * @internal
     */
    final class GettextTest extends CIUnitTestCase
    {
        private Gettext $gt;

        protected function setUp(): void
        {
            parent::setUp();

            $gtConfig = config(GettextConfig::class);
            $appConfig = config(App::class);
            $appConfig->supportedLocales = ['en', 'pl'];
            $gtConfig->allowedDomains = ['messages', 'errors'];

            $GLOBALS['gettext_stub'] = [];
            $GLOBALS['ngettext_stub'] = [];
            $GLOBALS['dgettext_stub'] = [];
            $GLOBALS['dngettext_stub'] = [];

            $this->gt = new Gettext($gtConfig, $appConfig);
        }

        public function testSetLocale()
        {
            $this->gt->setLocale('pl');
            $this->assertSame('pl_PL.utf8', getenv('LC_ALL'));
        }

        public function testPgettextFallsBackToMsgid()
        {
            $this->assertSame('Edit', $this->gt->pgettext('menu', 'Edit'));
        }

        public function testNpgettextReturnsPluralTranslation()
        {
            $context = 'library';
            $msgid = 'book';
            $msgidPlural = 'books';
            $pluralKey = $context . "\x04" . $msgidPlural;
            $GLOBALS['ngettext_stub'][$pluralKey] = 'Bücher';

            $this->assertSame('Bücher', $this->gt->npgettext($context, $msgid, $msgidPlural, 2));
        }

        public function testDpgettextRejectsUnknownDomain()
        {
            $this->expectException(\Michalsn\CodeIgniterGettext\Exceptions\GettextException::class);

            $this->gt->dpgettext('unknown', 'menu', 'Help');
        }

    }
}

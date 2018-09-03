<?php
/**
 * cakephp-locale-selector-middleware (https://github.com/smartsolutionsitaly/cakephp-locale-selector-middleware)
 * Copyright (c) 2018 Smart Solutions S.r.l. (https://smartsolutions.it)
 *
 * Locale Selector Middleware for CakePHP
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @category  cakephp-plugin
 * @package   cakephp-locale-selector-middleware
 * @author    Lucio Benini <dev@smartsolutions.it>
 * @copyright 2018 Smart Solutions S.r.l. (https://smartsolutions.it)
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      https://smartsolutions.it Smart Solutions
 * @since     1.0.0
 */

namespace SmartSolutionsItaly\CakePHP\I18n\Middleware;

use Cake\Core\Configure;
use Cake\I18n\I18n;
use Locale;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SmartSolutionsItaly\CakePHP\Database\Locale;

/**
 * Locale Selector Middleware.
 *
 * @package SmartSolutionsItaly\CakePHP\I18n\Middleware
 * @since 1.0.0
 * @see \Cake\I18n\Middleware\LocaleSelectorMiddleware
 */
class LocaleSelectorMiddleware
{
    protected $_locales = [];

    /**
     * LocaleSelectorMiddleware constructor.
     *
     * @param array|null $locales The allowed locales.
     * @since 1.0.1
     */
    public function __construct(array $locales = null)
    {
        if ($locales === null) {
            $this->_locales = Configure::read('App.locales');
        }
    }

    /**
     * @param ServerRequestInterface $request The request.
     * @param ResponseInterface $response The response.
     * @param callable $next The next middleware to call.
     * @return ResponseInterface A response.
     * @since 1.0.0
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $session = $request->getSession();
        $value = $request->getParam('locale', false);

        if (!$value) {
            $value = $request->getQuery('locale', false);
        }

        if (!$value) {
            $value = $request->getData('locale', false);
        }

        if (!$value && !$session->check('Config.language')) {
            $value = Locale::acceptFromHttp($request->getHeaderLine('Accept-Language'));
        }

        if ($locale = $this->setLocale($value)) {
            $session->write('Config.language', $locale);
            return $next($request, $response);
        }

        if ($session->read('Config.language') != Configure::read('App.locale')) {
            static::processLocale((string)$session->read('Config.language'));
        }

        return $next($request, $response);
    }

    /**
     * Sets the locale from the given value if matches the configured locales.
     *
     * @param string $value The locale string.
     * @return string|NULL If the given locale has been found returns it, otherwise NULL.
     * @since 1.0.0
     */
    protected function setLocale($value)
    {
        if ($value) {
            if ($this->_locales === ['*']) {
                return static::processLocale($value);
            }

            foreach ($this->_locales as $locale => $name) {
                $language = explode('_', $locale)[0];

                if (strcasecmp($locale, $value) === 0 || strcasecmp($value, $language) === 0) {
                    return static::processLocale($locale);
                }
            }
        }

        return null;
    }

    /**
     * Processes a locale, sets the framework's variables and returns it.
     *
     * @param string $locale The locale to process.
     * @return string The processed locale
     * @since 1.0.1
     */
    protected static function processLocale(string $locale)
    {
        $l = Locale::fromString($locale);
        $locale = $l->toString();
        
        I18n::setLocale($locale);
        Configure::write('App.locale', $locale);
        Configure::write('App.language', $l->getLanguage());

        return $locale;
    }
}

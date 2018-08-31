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

/**
 * Locale Selector Middleware.
 *
 * @package SmartSolutionsItaly\CakePHP\I18n\Middleware
 * @since 1.0.0
 * @see \Cake\I18n\Middleware\LocaleSelectorMiddleware
 */
class LocaleSelectorMiddleware
{
    /**
     * LocaleSelectorMiddleware constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param ServerRequestInterface $request The request.
     * @param ResponseInterface $response The response.
     * @param callable $next The next middleware to call.
     * @return ResponseInterface A response.
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $value = $request->getParam('locale', false);

        if (!$value) {
            $value = $request->getQuery('locale', false);
        }

        if (!$value) {
            $value = $request->getData('locale', false);
        }

        if (!$value) {
            $value = Locale::acceptFromHttp($request->getHeaderLine('Accept-Language'));
        }

        $session = $request->getSession();

        if ($value && ($locale = static::setLocale($value))) {
            $session->write('Config.language', $locale);
            return $next($request, $response);
        }

        if ($session->read('Config.language') != Configure::read('App.locale')) {
            i18n::setLocale($session->read('Config.language'));
        }

        return $next($request, $response);
    }

    /**
     * Sets the locale from the given value if matches the configured locales.
     *
     * @param string $value The locale string.
     * @return string|NULL "true" if the given locale has been found, otherwise "false".
     */
    protected static function setLocale($value)
    {
        if ($value) {
            if (Configure::read('App.locales') === ['*']) {
                I18n::setLocale($value);
                Configure::write('App.locale', $locale);
                Configure::write('App.language', $language);

                return $locale;
            }

            foreach (Configure::read('App.locales') as $locale => $name) {
                $language = explode('_', $locale)[0];

                if (strcasecmp($locale, $value) === 0 || strcasecmp($value, $language) === 0) {
                    I18n::setLocale($locale);
                    Configure::write('App.locale', $locale);
                    Configure::write('App.language', $language);

                    return $locale;
                }
            }
        }

        return null;
    }
}

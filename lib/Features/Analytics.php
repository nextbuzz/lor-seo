<?php

namespace NextBuzz\SEO\Features;

/**
 * Option page administration feature
 *
 * @author Bas de Kort <bas@nextbuzz.nl>
 */
class Analytics extends BaseFeature
{

    public function name()
    {
        return __("Google Analytics", "buzz-seo");
    }

    public function desc()
    {
        return __("Support for analytics.", "buzz-seo");
    }

    public function init()
    {
        add_action('admin_menu', array($this, 'createAdminMenu'));

        // Verification code somewhere on top
        add_action('wp_head', array($this, 'addSiteVerificationCode'), 0);

        // Make sure Google Analytics code is late in the head
        add_action('wp_head', array($this, 'addUACode'), 99);
    }

    /**
     * Add the administrator submenu
     */
    public function createAdminMenu()
    {
        // Add Settings Sub Option Page
        add_submenu_page('BuzzSEO', __('Google Analytics', 'buzz-seo'), __('Google Analytics', 'buzz-seo'), 'edit_posts', 'BuzzSEO_Analytics', array($this, "addAdminUI"));
    }

    /**
     * Create the admin interface pages.
     */
    public function addAdminUI()
    {
        \NextBuzz\SEO\PHPTAL\Settings\Analytics::factory()->render();
    }

    /**
     * Allow overriding the analytics Tracker var
     * @return string
     */
    private function trackerVar()
    {
        return esc_js(apply_filters('buzz_seo_ga_tracker_var', 'ga'));
    }

    public function addUACode()
    {
        $options = get_option('_settingsSettingsAnalytics', true);

        // Nothing checked, so do nothing
        if (!is_array($options) || !isset($options['uacode'])) {
            return;
        }

        $uacode = $options['uacode'];
        if (!empty($uacode) && preg_match("/\bua-\d{4,9}-\d{1,4}\b/i", $uacode)) {
            $code       = "(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
                })(window,document,'script','//www.google-analytics.com/analytics.js','" . $this->trackerVar() . "');";
            // Set UA code
            $domainName = 'auto';
            if (isset($options['setdomainname']) && !empty($options['setdomainname'])) {
                $domainName = $options['setdomainname'];
            }
            $code .= $this->trackerVar() . "('create', '" . esc_js($uacode) . "', '" . esc_js($domainName) . "');";

            // Anonymize
            if (isset($options['anonymize'])) {
                $code .= $this->trackerVar() . "('set', 'anonymizeIp', true);";
            }

            // Track user ID
            $userId = intval(get_current_user_id());
            if (isset($options['userid']) && $userId > 0) {
                $code .= $this->trackerVar() . "('set', 'userId', " . $userId . ");";
            }

            // Display advertising
            if (isset($options['displayadvertising'])) {
                $code .= $this->trackerVar() . "('require', 'displayfeatures');";
            }

            // Track pageview
            $code .= $this->trackerVar() . "('send', 'pageview');";

            // Output script
            echo "<script>{$code}</script>";
        }
    }

    public function addSiteVerificationCode()
    {
        $options = get_option('_settingsSettingsAnalytics', true);

        // Nothing checked, so do nothing
        if (!is_array($options) || !isset($options['siteverification'])) {
            return;
        }

        $verificationcode = $options['siteverification'];
        if (!empty($verificationcode)) {
            echo '<meta name="google-site-verification" content="' . esc_attr($verificationcode) . '" />' . PHP_EOL;
        }
    }

}
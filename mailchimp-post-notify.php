<?php
/**
 * Plugin Name: Mailchimp Post Notification
 * Plugin URI: http://github.com/
 * Description: Instant post notifications via Mailchimp
 * Version: 1.1
 * Author: Mori Fihri
 * Author URI: http://github.com
 * Text Domain: mailchimp-post-notification
 * Domain Path: /languages
 */
require_once (dirname(__FILE__) . '/lib/class-tgm-plugin-activation.php');

function dms3mis_fs() {
    global $dms3mis_fs;

    if (!isset($dms3mis_fs)) {
        // Include Freemius SDK.
        require_once dirname(__FILE__) . '/freemius/start.php';

        $dms3mis_fs = fs_dynamic_init(array(
            'id' => '741',
            'slug' => 'mailchimp-post-notification',
            'type' => 'plugin',
            'public_key' => 'pk_1dcad007b3a79a84081fc30ababfd',
            'is_premium' => false,
            'has_premium_version' => false,
            'has_addons' => false,
            'has_paid_plans' => false,
            'menu' => array(
                'first-path' => 'plugins.php',
                'account' => false,
                'contact' => false,
                'support' => false,
            ),
        ));
    }

    return $dms3mis_fs;
}

// Init Freemius.
dms3mis_fs();

if (!class_exists("MailchimpPostNotify")):
    include "mailchimp/mailchimppostnotify.php";
endif;

$postNotifiyInstant= new mailchimpPostNotifiyInstant;

class mailchimpPostNotifiyInstant {

    const TEXT_DOMAIN = "mailchimp-post-notification";
    const OPTIONS_OLD = "dmst_mc_immediate_options";
    const OPTIONS = "postNotifyMCInstant";
    const DIRECTOPTIONS = "postNotifyMCInstant_options";
    const OPTION_APIKEY = "API";
    const OPTION_MAXGROUPS = "MaxGroups";
    const OPTION_LISTGROUPS = "listgroups";
    const OPTION_LISTGROUP = "listgroup";
    const OPTION_POSTTYPE = "posttype";
    const OPTION_CONDITIONS = "conditions";
    const OPTION_TAXONOMYTERM = "taxonomyterm";
    const OPTION_TEMPLATE = "template";
    const OPTION_LOCATOR = "locator";
    const OPTION_CREATEONLY = "createonly";
    const FIELD_FORCESEND = "postnotifymcinstantsend";
    const FIELD_LOG = "postnotifymcinstantlog";
    const TRANS_LISTS = "mailchimpPostNotifiyLists";
    const TRANS_TIMESTAMP = "mailchimpPostNotifiyListsTimeStamp";
    const TRANS_TEMPLATES = "mailchimpPostNotifiyTemplates";
    const TRANS_LOCATORS = "mailchimpPostNotifiyLocators";

    private $pluginURL;
    private $pluginPath;
    private $langDir;
    private $mcSession;
    private $printScripts = false;

    function __construct() {
        $this->pluginURL = plugin_dir_url(__FILE__);
        $this->pluginPath = plugin_dir_path(__FILE__);
        $this->langDir = dirname(plugin_basename(__FILE__)) . '/languages';

        add_action('plugins_loaded', array($this, 'plugin_loaded'));
        add_action("init", array($this, "mailchimp_init"), 99);
        add_action('tgmpa_register', array($this, 'required_plugins'));
        add_action('tf_create_options', array($this, "tfoptions"));
        add_action("wp_ajax_postnotifymcinstantLoadLists", array($this, "ajax_loadLists"));
        // You can uncomment this line if you want to debug this web service
        // add_action("wp_ajax_nopriv_dms3mcimmediateLoadLists", array($this, "ajax_loadLists"));
        add_action("admin_enqueue_scripts", array($this, "jsAdmin"));
        add_action('save_post', array($this, 'sendIfRequired'));
        add_action('add_meta_boxes', array($this, 'add_metaboxes'));
    }

    function plugin_loaded() {
        load_plugin_textdomain("mailchimp-post-notification", false, $this->langDir);
    }

    /**
     * Initializes mailchimp
     * @since 3.0
     */
    function mailchimp_init() {
        if (!$this->mcSession):
            if (class_exists("TitanFramework")):
                $titan = TitanFramework::getInstance(self::OPTIONS);
                $api = $titan->getOption(self::OPTION_APIKEY);
                $this->mcSession = MailchimpPostNotify::MailChimpSession($api);
            endif;
        endif;
    }

    function required_plugins() {
        $plugins = array(
//            array(
//                'name'     => 'Meta Box',
//                'slug'     => 'meta-box',
//                'required' => true
//            ),
            array(
                'name' => 'Titan Framework',
                'slug' => 'titan-framework',
                'required' => true
            ),
        );
        $config = array(
            'id' => 'mailchimp-post-notification', // Unique ID for hashing notices for multiple instances of TGMPA.
            'default_path' => '', // Default absolute path to bundled plugins.
            'menu' => 'tgmpa-install-plugins', // Menu slug.
            'parent_slug' => 'plugins.php', // Parent menu slug.
            'capability' => 'manage_options', // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
            'has_notices' => true, // Show admin notices or not.
            'dismissable' => true, // If false, a user cannot dismiss the nag message.
            'dismiss_msg' => '', // If 'dismissable' is false, this message will be output at top of nag.
            'is_automatic' => false, // Automatically activate plugins after installation or not.
            'message' => '', // Message to output right before the plugins table.
        );

        tgmpa($plugins, $config);
    }

    function tfoptions() {
        $titan = TitanFramework::getInstance(self::OPTIONS);
        $panel = $titan->createAdminPanel(array(
            'name' => __("Post Notification - Mailchimp Instant Send", 'mailchimp-post-notification'),
            'id' => "dms3mailchimpimmediate",
            'title' => __("Mailchimp Instant Send", 'mailchimp-post-notification'),
            'parent' => 'options-general.php',
        ));
//TODO 'mailchimp-post-notification'
        $tabConfig = $panel->createTab(array(
            'name' => __('General Options', 'mailchimp-post-notification'),
            'title' => __('General Options', 'mailchimp-post-notification'),
            'id' => 'configuration'
        ));
        $tabConfig->createOption(array(
            'name' => __("Mailchimp API Key", 'mailchimp-post-notification'),
            'id' => self::OPTION_APIKEY,
            'type' => "text",
        ));
        $tabConfig->createOption(array(
            'name' => __("Number of Condition Groups", 'mailchimp-post-notification'),
            'id' => self::OPTION_MAXGROUPS,
            'type' => "number",
            'default' => 2,
            'min' => 2,
            'desc' => __("Number of groups", 'mailchimp-post-notification')
            . "<br/><strong>" . __("WARNING: if you decrease this number, last groups will be deleted", 'mailchimp-post-notification') . "</strong>",
        ));
        $tabConfig->createOption(array(
            'name' => __("Create Only", 'mailchimp-post-notification'),
            'id' => self::OPTION_CREATEONLY,
            'type' => "checkbox",
            'default' => FALSE,
            'desc' => __("If checked Campaigns will be created in MailChimp but they are not sent. You can send them manually from MailChimp.", 'mailchimp-post-notification')
        ));
        $tabConfig->createOption(array(
            'type' => "save",
            'save' => __("Save Changes", 'mailchimp-post-notification'),
            'use_reset' => false
        ));


        $listoptions = $this->getListOptions();
        $templateoptions = $this->getTemplatesOptions();
        $templatesections = $this->getSectionOptions();
        $taxandterms = $this->getTaxTermsOptions();
        $opcions = $this->getOptions();
        $maxn = isset($opcions[self::OPTION_MAXGROUPS]) ? $opcions[self::OPTION_MAXGROUPS] : 2;
        $tabGrups = $panel->createTab(array(
            'name' => __('Conditions', 'mailchimp-post-notification'),
            'title' => __('Conditions', 'mailchimp-post-notification'),
            'id' => 'conditions',
        ));
        $tabGrups->createOption(array(
            'type' => "ajax-button",
            "id" => "loadLists",
            "desc" => __("This button will reload lists from MailChimp", 'mailchimp-post-notification') . "<br/>"
            . $this->getListsLastUpdate() .
            "<pre style='display:none;'>" . print_r($this->getLists(), true) . "</pre>",
            "action" => "postnotifymcinstantLoadLists",
            "label" => __("Load lists and groups from MailChimp", 'mailchimp-post-notification'),
            "wait_label" => __("Loading...", 'mailchimp-post-notification'),
            "success_callback" => "mailchimpPostNotifiyListsLoaded",
        ));
        for ($n = 0; $n < $maxn; $n++):
            $prefix = $this->getPrefix($n);
            $tabGrups->createOption(array(
                'type' => "heading",
                'name' => sprintf(__("Condition %s", 'mailchimp-post-notification'), $n),
            ));
            $tabGrups->createOption(array(
                'type' => "select-post-types",
                'name' => __('Post type', 'mailchimp-post-notification'),
                'id' => $prefix . self::OPTION_POSTTYPE,
            ));
            $tabGrups->createOption(array(
                'type' => "select",
                'name' => $maxn == 0 ? __('Conditions', 'mailchimp-post-notification') : "",
                'id' => $prefix . self::OPTION_CONDITIONS,
                'multiple' => true,
                'options' => $taxandterms,
            ));
            $tabGrups->createOption(array(
                'type' => "select",
                'name' => __('List & Group', 'mailchimp-post-notification'),
                'id' => $prefix . self::OPTION_LISTGROUP,
                'options' => $listoptions,
            ));
            $tabGrups->createOption(array(
                'type' => "select",
                'name' => __('Template', 'mailchimp-post-notification'),
                'id' => $prefix . self::OPTION_TEMPLATE,
                'options' => $templateoptions,
            ));
            $tabGrups->createOption(array(
                'type' => "select",
                'name' => __('Template Locator', 'mailchimp-post-notification'),
                'id' => $prefix . self::OPTION_LOCATOR,
                'options' => $templatesections,
            ));
            $tabGrups->createOption(array(
                'type' => "save",
                'save' => __("Save Changes", 'mailchimp-post-notification'),
                'use_reset' => false
            ));
        endfor;
        $oldConf = get_option(self::OPTIONS_OLD);
        if ($oldConf != ""):
            $tabOldConfig = $panel->createTab(array(
                'name' => __('Old Configuration', 'mailchimp-post-notification'),
                'title' => __('Old Configuration', 'mailchimp-post-notification'),
                'id' => 'oldConfiguration'
            ));
            $tabOldConfig->createOption(array(
                'type' => "note",
                'name' => __("Earlier version configuration", 'mailchimp-post-notification'),
                'desc' => "<pre>" . print_r($oldConf, true) . "</pre>",
            ));
        endif;
    }

    function sendIfRequired($postID) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return $postID;
        if (defined('DOING_AJAX') && DOING_AJAX) //Quick Edit doesn't send
            return $postID;
        $titan = TitanFramework::getInstance(self::OPTIONS);
        if (isset($_POST['hidden_post_status'])):
            // It is not a background update
            $oldStatus = $_POST['hidden_post_status'];
            $resend = isset($_POST['mc-postnotify-instant']);
            $post = get_post($postID);
            if (($post->post_status == 'publish' && $oldStatus != $post->post_status) || ($post->post_status == 'publish' && $resend)):
                $posttype = $post->post_type;
                $optionPostTypes = $this->getoptionPostTypes();
                if (in_array($posttype, $optionPostTypes)):
                    $post_tax_and_terms = $this->getPostTaxAndTerms($post);

                    $log = get_metadata($posttype, $postID, self::FIELD_LOG, true);
                    $log = "\n\n" . $log;
                    $log = date('Y/m/d H:i:s ') . __('Start checking conditions', 'mailchimp-post-notification') . "\n" . $log;

                    $options = $this->getOptions();
                    $maxn = $this->getoptionMaxGroups();

                    $metConditions = array();
                    for ($n = 0; $n < $maxn; $n++):
                        if ($this->getoptionPostType($n) != $posttype):
                            break;
                        endif;
                        $conditions = $this->getoptionConditions($n);
                        if (count($conditions)):
                            $nmet = count($conditions);
                            foreach ($conditions as $c):
                                list($cposttype, $ctax, $cterm) = explode("|", $c);
                                if ($cposttype == $posttype && array_key_exists($ctax . "-" . $cterm, $post_tax_and_terms)):
                                    $nmet--;
                                endif;
                            endforeach;
                            if ($nmet == 0): // S'han complert totes les condicions
                                $metConditions[] = array(
                                    'n' => $n,
                                    'conditions' => $conditions,
                                );
                            endif;
                        endif;
                    endfor;

                    $log = date('Y/m/d H:i:s ') . sprintf(__('%s conditions matched', 'mailchimp-post-notification'), count($metConditions)) . "\n" . print_r($metConditions, true) . "\n" . $log;
                    $log = date('Y/m/d H:i:s ') . __('Start to send', 'mailchimp-post-notification') . "\n" . $log;

                    $message = $this->composeMessage($post);
                    $title = $this->composeTitle($post);

                    $i = 0;
                    foreach ($metConditions as $metCondition):
                        $i++;
                        $n = $metCondition["n"];
                        $template = $this->getoptionTemplate($n);
                        $locator = $this->getoptionLocator($n);
                        $log = date('Y/m/d H:i:s ') . sprintf(__("Condition %s", 'mailchimp-post-notification'), $i) . " -  " . __('List to send', 'mailchimp-post-notification') . print_r($this->getoptionList($n), true) . "\n" . $log;
                        list($listid, $groupingid, $groupid) = explode("-", $this->getoptionList($n));
                        if ($groupingid == "0"):
                            $groupingid = "";
                        endif;
                        if ($groupid == "0"):
                            $groupid = "";
                        endif;

                        $cid = $this->campaignCreate($listid, $message, $template, $title, $groupingid, $groupid, $locator);

                        if ($cid):
                            if (!$this->getoptionCreateOnly()):
                                $success = $this->campaignSend($cid);
                                if ($success):
                                    $log = date('Y/m/d H:i:s ') . sprintf(__("Condition %s", 'mailchimp-post-notification'), $i) . " -  " . __('Campaign sent', 'mailchimp-post-notification') . "\n" . $log;
                                else:
                                    $log = date('Y/m/d H:i:s ') . sprintf(__("Condition %s", 'mailchimp-post-notification'), $i) . " -  " . __('Campaign not sent', 'mailchimp-post-notification') . "\n" . $log;
                                endif;
                            else:
                                $log = date('Y/m/d H:i:s ') . sprintf(__("Condition %s", 'mailchimp-post-notification'), $i) . " -  " . __('Campaign not sent due to configuration', 'mailchimp-post-notification') . "\n" . $log;
                            endif;
                        else:
                            $log = date('Y/m/d H:i:s ') . sprintf(__("Condition %s", 'mailchimp-post-notification'), $i) . " -  " . __('Error: Campaign not created.', 'mailchimp-post-notification') . "\n.$log";
                        endif;
                    endforeach;
                    $log = date('Y/m/d H:i:s ') . __('End to send', 'mailchimp-post-notification') . "\n" . $log;
                    update_post_meta($postID, self::FIELD_LOG, $log . "\n" . $oldlog);
                endif;
            endif;
        endif;
    }

function composeMessage($post) {
$post_link = get_post_permalink($post);
$text = "<p> A new or updated flood status has been published to the website:</p>";
$text .= "<h1><a href=$post_link>$post->post_title</a></h1>";
$text .= "<p>Click the title above to view the article at the MRCA website. </p>";
return apply_filters("mailchimpPostNotifiy-message", $text);
}  

    function composeTitle($post) {
        $text = $post->post_title;
        return apply_filters("mailchimpPostNotifiy-title", $text);
    }

    function saveListsLastUpdate() {
        set_transient(self::TRANS_TIMESTAMP, current_time("mysql"));
    }

    function getListsLastUpdate() {
        if (false === $timestamp = get_transient(self::TRANS_TIMESTAMP)) {
            return __("As far as we know, lists have never been loaded. Please load lists and groups", 'mailchimp-post-notification');
        }
        return __("List last update:", 'mailchimp-post-notification') . " " . mysql2date("r", $timestamp, true);
    }

    function saveLists($lists) {
        set_transient(self::TRANS_LISTS, $lists);
    }

    function saveTemplates($templates) {
        set_transient(self::TRANS_TEMPLATES, $templates);
    }

    function getLists() {
        return get_transient(self::TRANS_LISTS);
    }

    function ajax_loadLists() {
        $this->mailchimp_init();
        $lists = MailchimpPostNotify::GetLists($this->mcSession, true);
        $templates = MailchimpPostNotify::GetTemplates($this->mcSession);
        if (is_array($lists) && is_array($templates)):
            $this->saveListsLastUpdate();
            $this->saveLists($lists);
            $this->saveTemplates($templates);
            wp_send_json_success(__("Lists loaded. Refreshing...", 'mailchimp-post-notification'));
            die();
        endif;
        wp_send_json_error(__("An error happened", 'mailchimp-post-notification'));
        die();
    }

    function jsAdmin() {
        wp_enqueue_script('mailchimpPostNotifiyadm', $this->pluginURL . 'js/admin.js', array('jquery'), '', true);
        wp_enqueue_style('mailchimpPostNotifiy', $this->pluginURL . 'css/admin.css');
    }

    function getListOptions() {
        $lists = $this->getLists();
        $result = array();
        $result[""] = __("— None —", 'mailchimp-post-notification');
        if ($lists):
            foreach ($lists as $l):
                $id = $l["id"] . "-0-0";
                $result[$id] = $l["name"];
                if (isset($l["interest-groups"])):
                    foreach ($l["interest-groups"] as $g):
                        foreach ($g["interests"] as $i):
                            $id = $l["id"] . "-" . $g["id"] . "-" . $i["id"];
                            $result[$id] = $l["name"] . /* " - " . $g["name"] . */ " - " . $i["name"];
                        endforeach;
                    endforeach;
                endif;
            endforeach;
        endif;
        return $result;
    }

    function getTemplatesOptions() {
        $templates = $this->getTemplates();
        $result = array();
        $result[""] = __("— None —", 'mailchimp-post-notification');
        if ($templates):
            foreach ($templates as $t):
                $result[$t["id"]] = $t["name"];
            endforeach;
        endif;
        return $result;
    }

    function getSectionOptions() {
        $templates = $this->getTemplates();
        $result = array();
        $result[""] = __("— None —", 'mailchimp-post-notification');
        if ($templates):
            foreach ($templates as $t):
                if (isset($t["sections"])):
                    foreach ($t["sections"] as $s):
                        $result[$s] = $s;
                    endforeach;
                endif;
            endforeach;
        endif;
        return $result;
    }

    function getTemplates() {
        if (false === ($templates = get_transient(self::TRANS_TEMPLATES))) {
            $templates = MailchimpPostNotify::GetTemplates($templates);
            set_transient(self::TRANS_TEMPLATES, $templates);
        }
        return $templates;
    }

    function getTaxTermsOptions() {
        $posttypes = get_post_types(array('public' => true), 'names');
        foreach ($posttypes as $posttype => $posttypename):
            $taxonomies = $this->getPosttypeTaxonomies($posttype);
            foreach ($taxonomies as $taxonomy):
                $terms = get_terms(array($taxonomy), array('hide_empty' => false));
                foreach ($terms as $term):
                    $select[$posttype . '|' . $taxonomy . '|' . $term->term_id] = $posttype . ' - ' /* . $taxonomy . ' - ' */ . $term->name;
                endforeach;
            endforeach;
        endforeach;
        return $select;
    }

    function getPrefix($n) {
        return self::OPTION_LISTGROUPS . "_" . $n . "_";
    }

    function getOptions() {
        return maybe_unserialize(get_option(self::DIRECTOPTIONS));
    }

    function getoptionMaxGroups() {
        $options = $this->getOptions();
        return $options[self::OPTION_MAXGROUPS];
    }

    function getoptionPostType($n) {
        $options = $this->getOptions();
        $prefix = $this->getPrefix($n);
        return $options[$prefix . self::OPTION_POSTTYPE];
    }

    function getoptionConditions($n) {
        $options = $this->getOptions();
        $prefix = $this->getPrefix($n);
        return $options[$prefix . self::OPTION_CONDITIONS];
    }

    function getoptionTemplate($n) {
        $options = $this->getOptions();
        $prefix = $this->getPrefix($n);
        return $options[$prefix . self::OPTION_TEMPLATE];
    }

    function getoptionLocator($n) {
        $options = $this->getOptions();
        $prefix = $this->getPrefix($n);
        return $options[$prefix . self::OPTION_LOCATOR];
    }

    function getoptionList($n) {
        $options = $this->getOptions();
        $prefix = $this->getPrefix($n);
        return $options[$prefix . self::OPTION_LISTGROUP];
    }

    function getoptionPostTypes() {
        $options = $this->getOptions();
        $result = array();
        $maxn = $this->getoptionMaxGroups();
        for ($n = 0; $n < $maxn; $n++):
            if ($options[$this->getPrefix($n) . self::OPTION_POSTTYPE] != ""):
                $result[$options[$this->getPrefix($n) . self::OPTION_POSTTYPE]] = $options[$this->getPrefix($n) . self::OPTION_POSTTYPE];
            endif;
        endfor;
        $result = array_keys($result);
        return $result;
    }

    function getoptionCreateOnly() {
        $options = $this->getOptions();
        return $options[self::OPTION_CREATEONLY];
    }

    function getPosttypeTaxonomies($p) {
        $taxs = get_taxonomies(array('show_ui' => true), 'objects');
        $result = array();
        foreach ($taxs as $name => $tax):
            if (in_array($p, $tax->object_type)):
                $result[] = $name;
            endif;
        endforeach;
        return $result;
    }

    function getPostTaxAndTerms($post) {
        $result = array();
        $taxonomies = $this->getPosttypeTaxonomies($post->post_type);
        foreach ($taxonomies as $taxonomy):
            $terms = wp_get_post_terms($post->ID, $taxonomy);
            foreach ($terms as $term):
                $result[$taxonomy . '-' . $term->term_id] = array(
                    'taxonomomy' => $taxonomy,
                    'term_id' => $term->term_id
                );
            endforeach;
        endforeach;
        return $result;
    }

    function campaignCreate($listID, $content, $templateID = "", $subject = "", $interestCategory = "", $interest = "", $templateSection = "") {
        $session = $this->mcSession;
        $list = MailchimpPostNotify::GetList($session, $listID);
        if ($list):
            $cname = $list->name;
            $cfrom = $list->campaign_defaults->from_name;
            $cemail = $list->campaign_defaults->from_email;
            if (!isset($subject)):
                $csubject = $list->campaign_defaults->subject;
            else:
                $csubject = $subject;
            endif;
            $settings = array(
                "title" => apply_filters("mailchimpPostNotifiy-campaign", $cname . " - " . current_time("mysql")),
                "subject_line" => $csubject,
                "from_name" => $cfrom,
                "reply_to" => $cemail,
            );
            $cid = MailchimpPostNotify::CampaignCreate($session, DeMomentSomTresMailChimp::REGULAR_CAMPAIGN, $listID, $settings, $interestCategory, $interest);
            if (!$cid):
                return;
            endif;
            $filled = MailchimpPostNotify::CampaignSetContent($session, $cid, $content, $templateID, $templateSection);
            if (!$filled):
                return;
            endif;
            return $cid;
        else:
            return false;
        endif;
    }

    function campaignSend($cid) {
        $session = $this->mcSession;
        $result = MailchimpPostNotify::CampaignSend($session, $cid);
        return $result;
    }

    function add_metaboxes() {
        $posttypes = $this->getoptionPostTypes();
        foreach ($posttypes as $posttype):
            add_meta_box('mc-postnotify-resend', __('Send', 'mailchimp-post-notification'), array($this, 'resend_metabox'), $posttype, 'side', 'high');
            add_meta_box('mc-postnotify-log', __('Log Mailchimp Immediate Send', 'mailchimp-post-notification'), array($this, 'log_metabox'), $posttype, 'advanced', 'low');
        endforeach;
    }

    function resend_metabox($post) {
        if ($post->post_status == 'publish'):
            echo '<p>' . __('Check the field to force resend to mailchimp as a published content is updated.', 'mailchimp-post-notification') . '</p>';
            echo '<p><input name="mc-postnotify-instant" type="checkbox" ' . checked(false, true, false) . '/>' . __('Force send', 'mailchimp-post-notification') . '</p>';
        else:
            echo '<p>' . __('The content will be sent to mailchimp when published if linked to any of the active taxonomy terms', 'mailchimp-post-notification') . '</p>';
        endif;
    }

    function log_metabox($post) {
        echo '<pre>' . print_r(get_post_meta($post->ID, self::FIELD_LOG, true), true) . '</pre>';
    }

}

?>

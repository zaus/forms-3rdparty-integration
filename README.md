# Forms: 3rd-Party Integration #

_(please note, the following was poorly copied from the Wordpress readme)_

----

**Contributors:** zaus, atlanticbt, spkane

**Donate link:** http://drzaus.com/donate

**Tags:** contact form, form, contact form 7, CF7, gravity forms, GF, CRM, mapping, 3rd-party service, services, remote request

**Requires at least:** 3.0

**Tested up to:** 4.3

**Stable tag:** trunk

**License:** GPLv2 or later


Send contact form submissions from other plugins to multiple external services e.g. CRM.  Configurable, custom field mapping, pre/post processing.

## Description ##

Send [Contact Form 7], [Gravity Forms], or [Ninja Forms] Submissions to a 3rd-party Service, like a CRM.  Multiple configurable services, custom field mapping.  Provides hooks and filters for pre/post processing of results.  Allows you to send separate emails, or attach additional results to existing emails.  Comes with a couple examples of hooks for common CRMs (listrak, mailchimp, salesforce).  Check out the FAQ section for add-on plugins that extend this functionality, like sending XML/SOAP posts, setting headers, and dynamic fields.

The plugin essentially makes a remote request (POST) to a service URL, passing along remapped form submission values.

Based on idea by Alex Hager "[How to Integrate Salesforce in Contact Form 7]".

Original plugin, [Contact Form 7: 3rdparty Integration] developed with the assistance of [AtlanticBT].  Current plugin sponsored by [Stephen P. Kane Consulting].  Please submit bugs / support requests to [GitHub issue tracker] in addition to the Wordpress Support Forums because the Forums do not send emails.

[Ninja Forms]: http://ninjaforms.com/ "Ninja Forms"
[Gravity Forms]: http://www.gravityforms.com/ "Gravity Forms"
[Contact Form 7]: http://wordpress.org/extend/plugins/contact-form-7/ "Contact Form 7"
[How to Integrate Salesforce in Contact Form 7]: http://www.alexhager.at/how-to-integrate-salesforce-in-contact-form-7/ "Original Inspiration"
[Contact Form 7: 3rdparty Integration]: http://wordpress.org/extend/plugins/contact-form-7-3rd-party-integration/ "CF7 Integration"
[AtlanticBT]: http://www.atlanticbt.com/ "Atlantic BT: Custom Website and Web-application Services"
[Stephen P. Kane Consulting]: http://www.stephenpkane.com/ "Website Design and Internet Marketing Services"
[GitHub issue tracker]: https://github.com/zaus/forms-3rdparty-integration/issues "GitHub issue tracker"


## Installation ##

1. Unzip, upload plugin folder to your plugins directory (`/wp-content/plugins/`)
2. Make sure at least one of [Contact Form 7], [Gravity Forms], or [Ninja Forms] is installed
3. Activate plugin
4. Go to new admin subpage _"3rdparty Services"_ under the CF7 "Contact" menu or Gravity Forms (or Ninja Forms) "Forms" menu and configure services + field mapping.
5. Turn on 'debug mode' to get emailed a copy of the submission+response data, until you're satisfied everything works, then turn it off

[Contact Form 7]: http://wordpress.org/extend/plugins/contact-form-7/ "Contact Form 7"
[Gravity Forms]: http://www.gravityforms.com/ "Gravity Forms"
[Ninja Forms]: http://ninjaforms.com/ "Ninja Forms"

## Frequently Asked Questions ##

### I need help / My form isn't working ###

Turn on 'debug mode' from the admin page to send you an email with:

* the current plugin configuration, including field mappings
* the user submission (as provided by the form plugin, CF7/GF/Ninja)
* the post as sent to the service (applied mapping)
* the response sent back from the service, which hopefully includes error codes or explanations (often is the raw HTML of a success/failure page)

Submit an issue to the [GitHub issue tracker] in addition to / instead of the WP Support Forums.

### How do I add / configure a service? ###

See [Screenshots](#screenshots) for visual examples.

Essentially,

1. Name your service.
2. Enter the submission URL -- if your "service" provides an HTML form, you would use the form action here.
3. Choose which forms will submit to this service ("Attach to Forms").
4. Set the default "success condition", or leave blank to ignore (or if using post processing, see [Hooks](#hooks) - this just looks for the provided text in the service response, and if present assumes "success"
4. Set an optional "failure message" to show if the remote request fails.  Can include the "nice explanation" as well as the original message provided by the contact form plugin.
5. Allow hooks for further processing - unchecking it just saves minimal processing power, as it won't try to execute filters.
6. Map your form submission values (from the CF7/GF field tags) to expected fields for your service.
    * 1:1 mapping given as the _name_ (from the HTML input) of the CF7/GF field and the _name_ of the 3rdparty field
    * For GF and Ninja Forms, you may map either by the field name or the field label
    * You can also provide static values by checking the "Is Value?" checkbox and providing the value in the "Form Submission Field" column.
    * The "Label" column is optional, and just provided for administrative notes, i.e. so you can remind yourself what each mapping pertains to.
7. Add, remove, and rearrange mapping - basically just for visual clarity.
8. Use the provided hooks (as given in the bottom of the service block).
9. Add new services as needed, drag/drop mappings and service boxes.

### How can I pre/post process the request/results? ###

See section [Hooks](#hooks).  See plugin folder `/3rd-parties` for example code for some common CRMs, which you can either directly include or copy to your code.

[GitHub issue tracker]: https://github.com/zaus/forms-3rdparty-integration/issues "GitHub issue tracker"

### I need to submit multiple values as... ###

* By default, if more than one value appears in the post request for the same field/key, they will be joined by the 'separator' value like `&post-values=a,b,c`.
* However, if you use `[]` as the separator it will instead create multiple keys like `&post-values[]=a&post-values[]=b&...`.
* Use `[#]` to retain the numerical index:  `&post-values[0]=a&post-values[1]=b&...`
* Use `[%]` to place the numerical index at desired location; specifically useful with nested fields via Xpost below (and issues [#11](https://github.com/zaus/forms-3rdparty-xpost/issues/11) and [#7](https://github.com/zaus/forms-3rdparty-xpost/issues/7)).

If you instead need to combine/nest fields, check out [Forms: 3rdparty Xpost](http://wordpress.org/plugins/forms-3rd-party-xpost/).

### How do I make a GET request instead of POST? ###

_from http://wordpress.org/support/topic/method-get?replies=2#post-5996489_

See 'Hooks' section, #5 of http://wordpress.org/plugins/forms-3rdparty-integration/other_notes/ and the [source code](https://github.com/zaus/forms-3rdparty-integration/blob/master/forms-3rdparty-integration.php#L478).

You'll need to perform `wp_remote_get` inside that filter and set `$post_args['response_bypass']` with the response, something like:

    function my_3rdparty_get_override($post_args, $service, $form) {
        $post_args['response_bypass'] = wp_remote_get($service['url'], $post_args);
        return $post_args;
    }

### What about Hidden Fields? ###

Using hidden fields can provide an easier way to include arbitrary values on a per-form basis, rather than a single "Is Value?" in the Service mapping, as you can then put your form-specific value in the hidden field, and map the hidden field name generically.

For convenience, you can install the [Contact Form 7 Modules: Hidden Fields].  This plugin originally included the relevant code, but it was causing issues on install, so is no longer bundled with it.

[Contact Form 7 Modules: Hidden Fields]: http://wordpress.org/extend/plugins/contact-form-7-modules/ "Hidden Fields from CF7 Modules"

### How do I export/import settings? ###
Use the "Forms 3rdparty Migration" plugin http://wordpress.org/plugins/forms-3rdparty-migrate/, which lets you export and import the raw settings as JSON.
You can also export settings from the original plugin [Contact Form 7: 3rdparty Integration](http://wordpress.org/extend/plugins/contact-form-7-3rd-party-integration/) and "upgrade" them for this plugin (although > 1.6.1 you will need to reselect forms).
Also at https://github.com/zaus/forms-3rdparty-migrate

### How do I map url parameters? ###
Use the "Dynamic Fields" plugin: http://wordpress.org/plugins/forms-3rdparty-dynamic-fields/
Also at https://github.com/zaus/forms-3rdparty-dynamicfields

### How do I send XML/submit to SOAP? ###
For simple xml containers try the "Forms 3rdparty Xpost" plugin: http://wordpress.org/plugins/forms-3rd-party-xpost/
Also at https://github.com/zaus/forms-3rdparty-xpost

### How do I set headers? ###
You can also set headers with "Forms 3rdparty Xpost" plugin: http://wordpress.org/plugins/forms-3rd-party-xpost/
Also at https://github.com/zaus/forms-3rdparty-xpost

### How do I show a custom message on the confirmation screen? ###
The failure message is shown by default if the 3rdparty post did not succeed.  You can add custom messaging to the plugin's (GF, CF7, Ninja) email or success screen response with something like:

    class MyPlugin {
        public function MyPlugin() {
            add_filter('Forms3rdPartyIntegration_service', array(&$this, 'adjust_response'), 10, 2);
        }

        public function adjust_response($body, $refs) {
            // use 'attach' to inject to regular email
            // use 'message' to inject to page
            $refs['attach'] = 'custom message in email';
            $refs['message'] = 'custom message on page';
        }
    }
    new MyPlugin(); // attach hook

### How do I conditionally submit? (if field == ...) ###
Use hook '...use_submission' to check the form submission (pre-mapping), making sure to pick the appropriate scenario, like:

    add_filter('Forms3rdPartyIntegration_use_submission', 'f3i_conditional_submit', 10, 3);
    function f3i_conditional_submit($use_this_form, $submission, $sid) {
        // if there was a specific value -- skip
        if(isset($submission['maybe-send']) && 'no' == $submission['maybe-send']) return false;
        // if there was a specific value -- use
        if(isset($submission['maybe-send']) && 'yes' == $submission['maybe-send']) return $use_this_form; // or true, i guess
        // if there was a value for it (e.g. for checkboxes) -- skip
        if(isset($submission['if-not-send'])) return false;
        // if there was a value for it (e.g. for checkboxes) -- use
        if(isset($submission['if-send']) && !empty($submission['if-send'])) return $use_this_form; // or true, i guess
        
        return $use_this_form; // or `false`, depending on your desired default
    }

If you want to check _after_ the fields have been mapped, you can "reuse" the hook '...service_filter_args' and return `false` to skip, rather than bypass:

    add_filter('Forms3rdPartyIntegration_service_filter_args', 'f3i_conditional_post', 10, 3);
    function f3i_conditional_post($post_args, $service, $form) {
        // your skip scenarios, checking `body` subarray instead
        if(isset($post_args['body']['maybe-send']) && ...) return false;

        // regular behavior
        return $post_args;
    }


## Screenshots ##

__Please note these screenshots are from the previous plugin incarnation, but are still essentially valid.__

1. Admin page - create multiple services, set up debugging/notice emails, example code
![Admin page - create multiple services, set up debugging/notice emails, example code](http://s.w.org/plugins/forms-3rdparty-integration/screenshot-1.png)

2. Sample service - mailchimp integration, with static and mapped values
![Sample service - mailchimp integration, with static and mapped values](http://s.w.org/plugins/forms-3rdparty-integration/screenshot-2.png)

3. Sample service - salesforce integration, with static and mapped values
![Sample service - salesforce integration, with static and mapped values](http://s.w.org/plugins/forms-3rdparty-integration/screenshot-3.png)


## Changelog ##

### 1.6.6.4 ###
* fix array value without index placeholder bug introduced in [github #43](https://github.com/zaus/forms-3rdparty-integration/issues/43)
* final bugfix to #55 (default options `mode` array)
* tried to address [Xpost issue #7](https://github.com/zaus/forms-3rdparty-xpost/issues/7), but not the right place for it

### 1.6.6.3 ###
* bugfixes [#53](https://github.com/zaus/forms-3rdparty-integration/issues/53) and [#55](https://github.com/zaus/forms-3rdparty-integration/issues/55)

### 1.6.6.1 ###
* debug logging hook
* fixed #52 - some hosting providers rejected arbitrary sender addresses
* more options for debug mail failure logging

### 1.6.6 ###
* Can now map GF and Ninja Forms by field label as well as id per issue #35 ([map by name](https://github.com/zaus/forms-3rdparty-integration/issues/35))

### 1.6.5.1 ###
* fix Github issue #43 ([valid success response codes](https://github.com/zaus/forms-3rdparty-integration/issues/43))
* fix Github issue #27 ([admin label](https://github.com/zaus/forms-3rdparty-integration/issues/27))
* exposed `$service` to hook `get_submission` to make extensions easier

### 1.6.4.3 ###
* fix escaped slashes for gravity forms submissions, see [GitHub issue #42](https://github.com/zaus/forms-3rdparty-integration/issues/42)

### 1.6.4.2 ###
* including original `$submission` in `service_filter_post` hook for [dynamicfields calc](https://wordpress.org/plugins/forms-3rdparty-dynamic-fields/)

### 1.6.4.1 ###
* quick fix for global section toggle bug

### 1.6.4 ###
* conditional submission hooks (see FAQ)
* removed somewhat useless 'can-hook' setting, since I assume everybody wants success processing.  Comment via github or author website contact form if you really need it.

### 1.6.3.1 ###
* Fix for longstanding (?) Firefox admin bug (issue #36) preventing field editing/input selection

### 1.6.3 ###
* fix form plugin checking when multiple contact form plugins used at same time

### 1.6.1 ###
* integration with [Ninja Forms](http://www.ninjaforms.com)
* refactored CF7 and GF integrations to take advantage of new FPLUGIN base (to make future integrations easier)
* defined upgrade path

Due to the new common form extension base, the way forms are identified in the settings has been changed.
Deactivating and reactivating the plugin (which happens automatically on upgrade, but not FTP or other direct file changes) should correct your existing settings.

Depending on how many services you have configured, the upgrade path may DESELECT your form selections in each service or otherwise break some configurations.
If you are concerned this may affect you, please [export](https://github.com/zaus/forms-3rdparty-migrate) the settings so you can reapply your selections.


### 1.4.9 ###
Updated cf7 plugin to match [their latest changes](http://contactform7.com/2014/07/02/contact-form-7-39-beta/).
* using new way to access properties
* removed remaining support for all older versions of CF7 (it was just getting complicated)

### 1.4.8.1 ###
Trying to add some clarity to the admin pages

### 1.4.8 ###
* multiple values treated differently depending on separator: 'char', `[]`, or `[#]`
* static values treated the same as dynamic (so they get above processing)
* fix: php5 constructor re: https://github.com/zaus/forms-3rdparty-integration/issues/6

### 1.4.7 ###
* totally removing hidden field plugin -- seems like even though it wasn't referenced, it may have caused the "invalid header" error during install
* admin ui - js fixes (configurable section icons via `data-icon`; entire metabox title now toggles accordion)
* stripslashes on submission to fix apostrophes in 'failure response' textarea

### 1.4.6 ###
* hook `...service_filter_args` to allow altering post headers, etc
* fix: removed more args-by-reference (for PHP 5.4 issues, see support forum requests)
* tested with WP 3.8, CF7 3.6

### 1.4.5 ###
* fix: failure response attaches to 'onscreen message' for Gravity Forms
* fix: (actually part of the next feature) failure response shows onscreen for Contact Form 7
* customize the failure response shown onscreen -- new admin setting per service (see description)

### 1.4.4 ###
* protecting against unattached forms
* Github link
* global post filter `Forms3rdPartyIntegration_service_filter_post` in addition to service-specific with suffix `_0`; accepts params `$post`, `$service`, `$form`, `$sid`
* admin options hook `Forms3rdPartyIntegration_service_settings`, `..._metabox`
* fix: gravityforms empty 'notification' field
* fix: admin ui -- 'hooks' toggle on metabox clone, row clone fieldname
* fix: service hooks not fired multiple times when both GF and CF7 plugins are active
* fix: Gravityforms correctly updates $form array

### 1.4.3 ###
* Fixed "plugin missing valid header" caused by some PHP versions rejecting passing variable by reference (?) as reported on Forum support topics ["Error on install"](http://wordpress.org/support/topic/error-on-install-6) and ["The plugin does not have a valid header"](http://wordpress.org/support/topic/the-plugin-does-not-have-a-valid-header-34), among others
* Rewrote admin javascript to address style bug as reported on forum post ["fields on mapping maintenance screen misaligned"](http://wordpress.org/support/topic/fields-on-mapping-maintenance-screen-misaligned) and via direct contact.  Really needed to be cleaned up anyway, I've learned more jQuery since then ;)
    * Dealt with weird issue where clicking a label also triggers its checkbox click
* Other general ui fixes
* More "verbose" endpoint-test script (headers, metadata as well as get/post)

### 1.4.2 ###

* Bugfixes
* cleaned up admin JS using delegate binding
* added "empty" checking for 3rdparty entries to avoid dreaded "I've deleted my mappings and can't do anything" error
* timeout option
* fixed CF7 form selection bug
* conditionally load CF7 or GF only if active/present; note that this plugin must `init` normally to check CF7

### 1.4.1 ###

* Bugfixes
* Added "Label" column for administrative notes

### 1.4.0 ###

* Forked from [Contact Form 7: 3rdparty Integration].
* Removed 'hidden field plugin' from 1.3.0, as it's specific to CF7.

### 1.3.2 ###

* Added failure hook - if your service fails for some reason, you now have the opportunity to alter the CF7 object and prevent it from mailing.

### 1.3.1 ###

* Added check for old version of CF7, so it plays nice with changes in newer version (using custom post type to store forms instead, lost original function for retrieval)
* see original error report http://wordpress.org/support/topic/plugin-forms-3rd-party-integration-undefined-function-wpcf7_contact_forms?replies=2#post-2646633

### 1.3.0 ###
moved "external" includes (hidden-field plugin) to later hook to avoid conflicts when plugin already called

### 1.2.3 ###
changed filter callback to operate on entire post set, changed name

### 1.2.2 ###
fixed weird looping problem; removed some debugging code; added default service to test file

### 1.2 ###
moved filter to include dynamic and static values; icons

### 1.1 ###
added configuration options, multiple services

### 1.0 ###
base version, just directly submits values


[Contact Form 7: 3rdparty Integration]: http://wordpress.org/extend/plugins/contact-form-7-3rd-party-integration/ "CF7 Integration"

## Upgrade Notice ##

### 1.6.1 ###
Due to the new common form extension base, the way forms are identified in the settings has been changed.
Deactivating and reactivating the plugin (which happens automatically on upgrade, but not FTP or other direct file changes) should correct your existing settings.
See Changelog for more details.

### 1.4.6 ###
* PHP 5.4 errors with (deprecated) passing arguments by reference should be fixed.
* Behavior change when reporting `$post` args in `on_response_failure` and similar -- now returns `$post_args`, which contains the header+body array as sent to new hook `...service_filter_args`
* Please submit a [GitHub issue](https://github.com/zaus/forms-3rdparty-integration/issues) in addition to making a support forum request if something is broken.

### 1.4.5 ###
You may need to configure the 'failure message', or at least refresh and save the admin settings, to avoid PHP 'empty index' warnings.

### 1.4.0 ###
Accommodates Gravity Forms.  Complete plugin rewrite, namespace since 1.3.2 incarnation as CF7 3rdparty Integration.  Incompatible with previous versions.

### 1.3.1 ###
See 1.3.0 notice

### 1.3.0 ###
Fixes should accomodate CF7 < v1.2 and changes to >= v1.2 -- please test and check when upgrading, and report any errors to the plugin forum.

## Hooks ##

_Please note that this documentation is in flux, and may not be accurate for latest rewrite 1.4.0_

1. `add_action('Forms3rdPartyIntegration_service_a#', $response, $param_ref);`
    * hook for each service, indicated by the `#` - _this is given in the 'Hooks' section of each service_
    * provide a function which takes `$response, &$results` as arguments
    * allows you to perform further processing on the service response, and directly alter the processing results, provided as `array('success'=>false, 'errors'=>false, 'attach'=>'', 'message' => '');`
        * *success* = `true` or `false` - change whether the service request is treated as "correct" or not
        * *errors* = an array of error messages to return to the form
        * *attach* = text to attach to the end of the email body
        * *message* = the message notification shown (from CF7 ajax response) below the form
    * note that the basic "success condition" may be augmented here by post processing
1. `add_action('Forms3rdPartyIntegration_service', $response, $param_ref, $sid);`
    * same as previous hook, but not tied to a specific service
2. `add_filter('Forms3rdPartyIntegration_service_filter_post_#, ...`
    * hook for each service, indicated by the `#` - _this is given in the 'Hooks' section of each service_
    * allows you to programmatically alter the request parameters sent to the service
    * should return updated `$post` array
2. `add_filter('Forms3rdPartyIntegration_service_filter_post', 'YOUR_HOOK', 10, 4);`
    * in addition to service-specific with suffix `_a#`; accepts params `$post`, `$service`, `$form`, `$sid`
2. `add_filter('Forms3rdPartyIntegration_service_filter_args', 'YOUR_HOOK', 10, 3);`
    * alter the [args array](http://codex.wordpress.org/Function_Reference/wp_remote_post#Parameters) sent to `wp_remote_post`
    * allows you to add headers or override the existing settings (timeout, body)
    * if you return an array containing the key `response_bypass`, it will skip the normal POST and instead use that value as the 3rdparty response; note that it must match the format of a regular `wp_remote_post` response.
    * Note: if using `response_bypass` you should consider including the original arguments in the callback result for debugging purposes.
3.  `add_action('Forms3rdPartyIntegration_remote_failure', 'mycf7_fail', 10, 5);`
    * hook to modify the Form (CF7 or GF) object if service failure of any kind occurs -- use like:
    
        function mycf7_fail(&$cf7, $debug, $service, $post, $response) {
            $cf7->skip_mail = true; // stop email from being sent
            // hijack message to notify user
            ///TODO: how to modify the "mail_sent" variable so the message isn't green?  on_sent_ok hack?
            $cf7->messages['mail_sent_ok'] = 'Could not complete mail request:** ' . $response['safe_message']; 
        }
    
    * needs some way to alter the `mail_sent` return variable in CF7 to better indicate an error - no way currently to access it directly.
4. `add_action('Forms3rdPartyIntegration_service_settings', 'YOUR_HOOK', 10, 3)`
    * accepts params `$eid`, `$P`, `$entity` corresponding to the index of each service entity and this plugin's namespace, and the `$entity` settings array
    * allows you to add a section to each service admin settings
    * name form fields with plugin namespace to automatically save:  `$P[$eid][YOUR_CUSTOM_FIELD]` $rarr; `Forms3rdPartyIntegration[0][YOUR_CUSTOM_FIELD]`
4. `add_action('Forms3rdPartyIntegration_service_metabox', 'YOUR_HOOK', 10, 2)`
    * accepts params `$P`, `$entity` corresponding to the index of each service entity and this plugin's namespace, and the `$options` settings array (representing the full plugin settings)
    * allows you to append a metabox (or anything else) to the plugin admin settings page
    * name form fields with plugin namespace to automatically save:  `$P[YOUR_CUSTOM_FIELD]` $rarr; `Forms3rdPartyIntegration[YOUR_CUSTOM_FIELD]`
6. `add_filter('Forms3rdPartyIntegration_debug_message', 'YOUR_HOOK', 10, 5);`
    * bypass/alternate debug logging

Basic examples provided for service hooks directly on plugin Admin page (collapsed box "Examples of callback hooks").  Code samples for common CRMS included in the `/3rd-parties` plugin folder.

## Stephen P. Kane Consulting ##

From [the website] and [Handpicked Tomatoes]:

**Transparent and Holistic Approach**

> Transparency is good. It's amazing how many web design sites hide who they are. There are lots of reasons, none of which are good for the customer. We don't do that. I'm Stephen Kane, principal web craftsman at HandpickedTomatoes, and I'm an Orange County based freelancer who occasionally works with other local freelancers and agencies to deliver quality web solutions at very affordable prices.
> We work to earn the right to be a trusted partner. One that you can turn to for professional help in strategizing, developing, executing, and maintaining your Internet presence.
> We take a holistic view. Even if a project is small, our work should integrate into the big picture. We craft web architecture and designs that become winning websites that are easy to use and to share. We custom build social network footprints on sites like linkedin, facebook, twitter, youtube, flickr, yelp!, and google places and integrate them into your website to leverage social marketing. We help you set up and execute email campaigns, with search engine marketing, with photography, with site copy and content and anything else that you need in order to have a successful Internet presence.
> Through this holistic approach, we work with clients to grow their sales, improve their brand recognition, and manage their online reputation.

[the website]: http://www.stephenpkane.com/ "Wordpress, Online Marketing, Social Media, SEO"
[Handpicked Tomatoes]: http://handpickedtomatoes.com/ "Website Design & Internet Marketing Services"  
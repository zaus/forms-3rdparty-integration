=== Forms: 3rd-Party Integration ===
Contributors: zaus, atlanticbt, skane
Donate link: http://drzaus.com/donate
Tags: contact form, form, contact form 7, CF7, gravity forms, GF, CRM, mapping, 3rd-party service, services, remote request
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: trunk
License: GPLv2 or later

Send contact form submissions from plugins CF7 or GF to multiple external services e.g. CRM.  Configurable, custom field mapping, pre/post processing.

== Description ==

Send [Contact Form 7][] or [Gravity Forms][] Submissions to a 3rd-party Service, like a CRM.  Multiple configurable services, custom field mapping.  Provides hooks and filters for pre/post processing of results.  Allows you to send separate emails, or attach additional results to existing emails.  Comes with a couple examples of hooks for common CRMs (listrak, mailchimp, salesforce).

The plugin essentially makes a remote request (POST) to a service URL, passing along remapped form submission values.

Based on idea by Alex Hager "[How to Integrate Salesforce in Contact Form 7][]".

Original plugin, [Contact Form 7: 3rdparty Integration][] developed with the assistance of [AtlanticBT][].  Current plugin sponsored by [Stephen P. Kane Consulting][].

[Gravity Forms]: http://www.gravityforms.com/ "Gravity Forms"
[Contact Form 7]: http://wordpress.org/extend/plugins/contact-form-7/ "Contact Form 7"
[How to Integrate Salesforce in Contact Form 7]: http://www.alexhager.at/how-to-integrate-salesforce-in-contact-form-7/ "Original Inspiration"
[Contact Form 7: 3rdparty Integration]: http://wordpress.org/extend/plugins/contact-form-7-3rd-party-integration/ "CF7 Integration"
[AtlanticBT]: http://www.atlanticbt.com/ "Atlantic BT: Custom Website and Web-application Services"
[Stephen P. Kane Consulting]: http://www.stephenpkane.com/ "Website Design and Internet Marketing Services"


== Installation ==

1. Unzip, upload plugin folder to your plugins directory (`/wp-content/plugins/`)
2. Make sure [Contact Form 7][]  or [Gravity Forms][] is installed
3. Activate plugin
4. Go to new admin subpage _"3rdparty Services"_ under the CF7 "Contact" menu or Gravity Forms "Forms" menu and configure services + field mapping.

[Contact Form 7]: http://wordpress.org/extend/plugins/contact-form-7/ "Contact Form 7"
[Gravity Forms]: http://www.gravityforms.com/ "Gravity Forms"

== Frequently Asked Questions ==

= How do I add / configure a service? =

See [Screenshots][] for visual examples.

Essentially,

1. Name your service
2. Enter the submission URL -- if your "service" provides an HTML form, you would use the form action here
3. Choose which forms will submit to this service ("Attach to Forms")
4. Set the default "success condition", or leave blank to ignore (or if using post processing, see [Hooks][] - this just looks for the provided text in the service response, and if present assumes "success"
5. Allow hooks for further processing - unchecking it just saves minimal processing power, as it won't try to execute filters
6. Map your form submission values (from the CF7/GF field tags) to expected fields for your service.  1:1 mapping given as the _name_ (from the HTML input) of the CF7/GF field and the name of the 3rdparty field; you can also provide static values by checking the "Is Value?" checkbox and providing the value in the "Form Submission Field" column.  The "Label" column is optional, and just provided for administrative notes, i.e. so you can remind yourself what each mapping pertains to.
7. Add, remove, and rearrange mapping - basically just for visual clarity.
8. Use the provided hooks (as given in the bottom of the service block)
9. Add new services as needed

= How can I pre/post process the request/results? =

See section [Hooks][].  See plugin folder `/3rd-parties` for example code for some common CRMs, which you can either directly include or copy to your code.

[Hooks]: /extend/plugins/forms-3rd-party-integration/other_notes#Hooks
[Screenshots]: /extend/plugins/forms-3rd-party-integration/screenshots

= What about Hidden Fields? =

Using hidden fields can provide an easier way to include arbitrary values on a per-form basis, rather than a single "Is Value?" in the Service mapping, as you can then put your form-specific value in the hidden field, and map the hidden field name generically.

This plugin includes another hidden field plugin for convenience from [Contact Form 7 Modules: Hidden Fields][].  I had kept the original plugin headers intact which confused some users, and so though it's no longer advertising itself on the plugin listing it's still bundled with this plugin.

[Contact Form 7 Modules: Hidden Fields]: http://wordpress.org/extend/plugins/contact-form-7-modules/ "Hidden Fields from CF7 Modules"

== Screenshots ==

__Please note these screenshots are from the previous plugin incarnation, but are still essentially valid.__

1. Admin page - create multiple services, set up debugging/notice emails, example code
2. Sample service - mailchimp integration, with static and mapped values
3. Sample service - salesforce integration, with static and mapped values


== Changelog ==

= 1.4.3 =
* Fixed "plugin missing valid header" caused by some PHP versions rejecting passing variable by reference (?) as reported on Forum support topics ["Error on install"](http://wordpress.org/support/topic/error-on-install-6) and ["The plugin does not have a valid header"](http://wordpress.org/support/topic/the-plugin-does-not-have-a-valid-header-34), among others
* Rewrote admin javascript to address style bug as reported on forum post ["fields on mapping maintenance screen misaligned"](http://wordpress.org/support/topic/fields-on-mapping-maintenance-screen-misaligned) and via direct contact.  Really needed to be cleaned up anyway, I've learned more jQuery since then ;)
** Dealt with weird issue where clicking a label also triggers its checkbox click

= 1.4.2 =

* Bugfixes
* cleaned up admin JS using delegate binding
* added "empty" checking for 3rdparty entries to avoid dreaded "I've deleted my mappings and can't do anything" error
* timeout option
* fixed CF7 form selection bug
* conditionally load CF7 or GF only if active/present; note that this plugin must `init` normally to check CF7

= 1.4.1 =

* Bugfixes
* Added "Label" column for administrative notes

= 1.4.0 =

* Forked from [Contact Form 7: 3rdparty Integration][].
* Removed 'hidden field plugin' from 1.3.0, as it's specific to CF7.

= 1.3.2 =

* Added failure hook - if your service fails for some reason, you now have the opportunity to alter the CF7 object and prevent it from mailing.

= 1.3.1 =

* Added check for old version of CF7, so it plays nice with changes in newer version (using custom post type to store forms instead, lost original function for retrieval)
* see original error report http://wordpress.org/support/topic/plugin-forms-3rd-party-integration-undefined-function-wpcf7_contact_forms?replies=2#post-2646633

= 1.3.0 =
moved "external" includes (hidden-field plugin) to later hook to avoid conflicts when plugin already called

= 1.2.3 =
changed filter callback to operate on entire post set, changed name

= 1.2.2 =
fixed weird looping problem; removed some debugging code; added default service to test file

= 1.2 =
moved filter to include dynamic and static values; icons

= 1.1 =
added configuration options, multiple services

= 1.0 =
base version, just directly submits values


[Contact Form 7: 3rdparty Integration]: http://wordpress.org/extend/plugins/contact-form-7-3rd-party-integration/ "CF7 Integration"

== Upgrade Notice ==

= 1.4.0 =
Accommodates Gravity Forms.  Complete plugin rewrite, namespace since 1.3.2 incarnation as CF7 3rdparty Integration.  Incompatible with previous versions.

= 1.3.1 =
See 1.3.0 notice

= 1.3.0 =
Fixes should accomodate CF7 < v1.2 and changes to >= v1.2 -- please test and check when upgrading, and report any errors to the plugin forum.

== Hooks ==

_Please note that this documentation is in flux, and may not be accurate for latest rewrite 1.4.0_

1. `add_action('Forms3rdPartyIntegration_service_a#',...`
    * hook for each service, indicated by the `#` - _this is given in the 'Hooks' section of each service_
    * provide a function which takes `$response, &$results` as arguments
    * allows you to perform further processing on the service response, and directly alter the processing results, provided as `array('success'=>false, 'errors'=>false, 'attach'=>'', 'message' => '');`
        * *success* = `true` or `false` - change whether the service request is treated as "correct" or not
        * *errors* = an array of error messages to return to the form
        * *attach* = text to attach to the end of the email body
        * *message* = the message notification shown (from CF7 ajax response) below the form
    * note that the basic "success condition" may be augmented here by post processing
2. `add_filter('Forms3rdPartyIntegration_service_filter_post_#, ...`
    * hook for each service, indicated by the `#` - _this is given in the 'Hooks' section of each service_
    * allows you to programmatically alter the request parameters sent to the service
3.  `add_action('Forms3rdPartyIntegration_remote_failure', 'mycf7_fail', 10, 5);`
    * hook to modify the Form (CF7 or GF) object if service failure of any kind occurs -- use like:
        function mycf7_fail(&$cf7, $debug, $service, $post, $response) {
            $cf7->skip_mail = true; // stop email from being sent
            // hijack message to notify user
            ///TODO: how to modify the "mail_sent" variable so the message isn't green?  on_sent_ok hack?
            $cf7->messages['mail_sent_ok'] = 'Could not complete mail request: ' . $response['safe_message']; 
        }
    * needs some way to alter the `mail_sent` return variable in CF7 to better indicate an error - no way currently to access it directly.

Basic examples provided for service hooks directly on plugin Admin page (collapsed box "Examples of callback hooks").  Code samples for common CRMS included in the `/3rd-parties` plugin folder.

== Stephen P. Kane Consulting ==

From [the website][] and [Handpicked Tomatoes][]:

**Transparent and Holistic Approach**

> Transparency is good. It's amazing how many web design sites hide who they are. There are lots of reasons, none of which are good for the customer. We don't do that. I'm Stephen Kane, principal web craftsman at HandpickedTomatoes, and I'm an Orange County based freelancer who occasionally works with other local freelancers and agencies to deliver quality web solutions at very affordable prices.
> We work to earn the right to be a trusted partner. One that you can turn to for professional help in strategizing, developing, executing, and maintaining your Internet presence.
> We take a holistic view. Even if a project is small, our work should integrate into the big picture. We craft web architecture and designs that become winning websites that are easy to use and to share. We custom build social network footprints on sites like linkedin, facebook, twitter, youtube, flickr, yelp!, and google places and integrate them into your website to leverage social marketing. We help you set up and execute email campaigns, with search engine marketing, with photography, with site copy and content and anything else that you need in order to have a successful Internet presence.
> Through this holistic approach, we work with clients to grow their sales, improve their brand recognition, and manage their online reputation.

[the website]: http://www.stephenpkane.com/ "Wordpress, Online Marketing, Social Media, SEO"
[Handpicked Tomatoes]: http://handpickedtomatoes.com/ "Website Design & Internet Marketing Services"
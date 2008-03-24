// Docu : http://tinymce.moxiecode.com/tinymce/docs/customization_plugins.html

// Load the language file
tinyMCE.importPluginLanguagePack('NextGEN', 'en,tr,de,sv,zh_cn,cs,fa,fr_ca,fr,pl,pt_br,nl,he,nb,ru,ru_KOI8-R,ru_UTF-8,nn,cy,es,is,zh_tw,zh_tw_utf8,sk,da');

var TinyMCE_NextGENPlugin = {
	/**
	 * Returns information about the plugin as a name/value array.
	 * The current keys are longname, author, authorurl, infourl and version.
	 *
	 * @returns Name/value array containing information about the plugin.
	 * @type Array 
	 */
	getInfo : function() {
		return {
			longname : 'NextGEN',
			author : 'NextGEN',
			authorurl : 'http://alexrabe.boelinger.com',
			infourl : 'http://alexrabe.boelinger.com',
			version : "1.0"
		};
	},

	/**
	 * Returns the HTML code for a specific control or empty string if this plugin doesn't have that control.
	 * A control can be a button, select list or any other HTML item to present in the TinyMCE user interface.
	 * The variable {$editor_id} will be replaced with the current editor instance id and {$pluginurl} will be replaced
	 * with the URL of the plugin. Language variables such as {$lang_somekey} will also be replaced with contents from
	 * the language packs.
	 *
	 * @param {string} cn Editor control/button name to get HTML for.
	 * @return HTML code for a specific control or empty string.
	 * @type string
	 */
	getControlHTML : function(cn) {
	 	switch (cn) {
			case "NextGEN":
				return tinyMCE.getButtonHTML(cn, 'lang_NextGEN_desc', '{$pluginurl}/nextgen.gif', 'mceNextGEN');
		}

		return "";
	},

	/**
	 * Executes a specific command, this function handles plugin commands.
	 *
	 * @param {string} editor_id TinyMCE editor instance id that issued the command.
	 * @param {HTMLElement} element Body or root element for the editor instance.
	 * @param {string} command Command name to be executed.
	 * @param {string} user_interface True/false if a user interface should be presented.
	 * @param {mixed} value Custom value argument, can be anything.
	 * @return true/false if the command was executed by this plugin or not.
	 * @type
	 */
	execCommand : function(editor_id, element, command, user_interface, value) {
	 
	 	// Handle commands
		switch (command) {
			// Remember to have the "mce" prefix for commands so they don't intersect with built in ones in the browser.
			case "mceNextGEN":
				// Do your custom command logic here.
				ngg_buttonscript();
				return true;
		}
		// Pass to next handler in chain
		return false;
	}

};

// Adds the plugin class to the list of available TinyMCE plugins
tinyMCE.addPlugin("NextGEN", TinyMCE_NextGENPlugin);


/** \brief mAutoPopup TinyMCE plugin
	\file editor_plugin.js
	\version 0.4.3
	\author Christophe SAUVEUR <christophe\@xhaleera.com>
	
	The mAutoPopup plugin for TinyMCE enables you with the ability to insert automatically the <!--no_mautopopup--> quicktag.
*/

(function() {
		tinymce.create('tinymce.plugins.mAutoPopup', {
					init: function(ed, url) {
						// Adding buttons
						ed.addButton('mautopopup_disable', {
									title: 'mautopopup.disable',
									cmd: 'mautopopup_disable',
									image: url + '/images/disable.gif'
						});
						ed.addButton('mautopopup_enable', {
									title: 'mautopopup.enable',
									cmd: 'mautopopup_enable',
									image: url + '/images/enable.gif'
						});
						ed.addButton('mautopopup_remove', {
									title: 'mautopopup.remove',
									cmd: 'mautopopup_remove',
									image: url + '/images/remove.gif'
						});
						
						// Commands
						ed.addCommand('mautopopup_disable', function() {
									ed.setContent(ed.getContent().replace(/<!--mautopopup:(enable|disable)-->/, ''));
									ed.execCommand('mceInsertContent', false, '<!--mautopopup:disable-->');
						});
						ed.addCommand('mautopopup_enable', function() {
									ed.setContent(ed.getContent().replace(/<!--mautopopup:(enable|disable)-->/, ''));
									ed.execCommand('mceInsertContent', false, '<!--mautopopup:enable-->');
						});
						ed.addCommand('mautopopup_remove', function() {
									ed.setContent(ed.getContent().replace(/<!--mautopopup:(enable|disable)-->/, ''));
						});
					},
					
					getInfo : function() {
						return {
							longname : 'mAutoPopup',
							author : 'Christophe SAUVEUR',
							authorurl : 'http://www.xhaleera.com',
							infourl : 'http://www.xhaleera.com/index.php/products/wordpress-mseries-plugins/mautopopup/',
							version : '0.4.2'
						};
					}
		});
		
		tinymce.PluginManager.add('mautopopup', tinymce.plugins.mAutoPopup);
})();

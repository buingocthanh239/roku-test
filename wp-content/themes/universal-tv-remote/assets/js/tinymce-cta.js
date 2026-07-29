(function () {
	tinymce.PluginManager.add('tvr_cta', function (editor) {
		editor.addButton('tvr_cta_button', {
			text: 'CTA',
			title: 'Chèn nút CTA (mặc định link App Store)',
			icon: false,
			onclick: function () {
				var defaultUrl = editor.getParam('tvr_cta_default_url', '');
				editor.windowManager.open({
					title: 'Chèn nút CTA',
					body: [
						{ type: 'textbox', name: 'text', label: 'Chữ trên nút', value: 'Tải app ngay' },
						{ type: 'textbox', name: 'url', label: 'Link (để trống = link App Store)', value: defaultUrl },
						{ type: 'listbox', name: 'style', label: 'Kiểu nút', values: [
							{ text: 'Nền đặc (mặc định)', value: 'solid' },
							{ text: 'Viền, nền trong suốt', value: 'outline' }
						] },
						{ type: 'textbox', name: 'color', label: 'Màu tùy chỉnh (vd: #ff0055 — để trống = màu Brand mặc định)', value: '' }
					],
					onsubmit: function (e) {
						var text = (e.data.text || '').trim() || 'Tải app ngay';
						var url = (e.data.url || '').trim() || defaultUrl;
						var style = e.data.style || 'solid';
						var color = (e.data.color || '').trim();
						var shortcode = '[tvr_cta text="' + editor.dom.encode(text) + '" url="' + editor.dom.encode(url) + '" style="' + style + '"';
						if (color) {
							shortcode += ' color="' + editor.dom.encode(color) + '"';
						}
						shortcode += ']';
						editor.insertContent(shortcode);
					}
				});
			}
		});
	});
})();

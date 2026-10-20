(function (wp) {
	'use strict';

	if (!wp || !wp.blocks) {
		return;
	}

	var registerBlockType = wp.blocks.registerBlockType;
	var createElement = wp.element.createElement;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;

	registerBlockType('oli-bis/notify-form', {
		apiVersion: 3,
		title: __('Back in stock form', 'oli-back-in-stock-notifications'),
		description: __('Displays the notify-me form for an out-of-stock product.', 'oli-back-in-stock-notifications'),
		category: 'woocommerce',
		icon: 'email',
		supports: {
			html: false
		},
		attributes: {
			productId: {
				type: 'number',
				default: 0
			}
		},
		edit: function (props) {
			return createElement(
				'div',
				{ className: 'oli-bis-block-editor' },
				createElement(
					InspectorControls,
					null,
					createElement(
						PanelBody,
						{ title: __('Form settings', 'oli-back-in-stock-notifications') },
						createElement(TextControl, {
							label: __('Product ID', 'oli-back-in-stock-notifications'),
							help: __('Leave empty to use the current product.', 'oli-back-in-stock-notifications'),
							type: 'number',
							value: props.attributes.productId || '',
							onChange: function (value) {
								props.setAttributes({ productId: parseInt(value, 10) || 0 });
							}
						})
					)
				),
				createElement(
					'p',
					null,
					__('Notify-me form for out-of-stock products.', 'oli-back-in-stock-notifications')
				)
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp);

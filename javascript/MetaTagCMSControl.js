// prepare the form when the DOM is ready

jQuery(document).ready(
	function() {
		MetaTagCMSControl.initOnce();
		MetaTagCMSControl.init();
	}
);
var MetaTagCMSControl = {

	fieldName: '',

	delegateRootSelector: "#MetatagOuterHolder",

	initOnce: function(){
		jQuery(MetaTagCMSControl.delegateRootSelector).on(
			"click",
			".batchactions a",
			function(event) {
				event.preventDefault();
				jQuery(".actions ul, tr.subsequentActions").slideToggle();
				return false;
			}
		);

		jQuery(MetaTagCMSControl.delegateRootSelector).on(
			"click",
			"a.ajaxify",
			function(event) {
				event.preventDefault();
				jQuery('body').addClass("loading");
				var url = jQuery(this).attr("href");
				jQuery.get(
					url,
					function(data) {
						jQuery('tbody').html(data);
						jQuery('.response').text("records updated ....");
						jQuery('body').removeClass("loading");
						MetaTagCMSControl.init();
					},
					"html"
				);

			}
		);

		jQuery(MetaTagCMSControl.delegateRootSelector).on(
			"change",
			" input, textarea",
			function() {
				jQuery(this).parent().removeClass("lowRes").addClass("highRes");
				MetaTagCMSControl.fieldName = jQuery(this).attr("id");
				jQuery('#MetaTagCMSControlForm').submit();
			}
		);
	},

	init: function(){
		var options = {
			target:             '.response',   // target element(s) to be updated with server response
			beforeSubmit:       MetaTagCMSControl.showRequest,  // pre-submit callback
			success:            MetaTagCMSControl.showResponse,  // post-submit callback
			error:              MetaTagCMSControl.showError,  // post-submit callback
			beforeSerialize:    MetaTagCMSControl.fixSerialize
			// other available options:
			//url:       url         // override for form's 'action' attribute
			//type:      type        // 'get' or 'post', override for form's 'method' attribute
			//dataType:  null        // 'xml', 'script', or 'json' (expected server response type)
			//clearForm: true        // clear all form fields after successful submit
			//resetForm: true        // reset the form after successful submit

			// jQuery.ajax options can be used here too, for example:
			//timeout:   3000
		};

		// bind form using 'ajaxForm'
		jQuery('#MetaTagCMSControlForm').ajaxForm(options);
		//submit on change
		jQuery("#MetatagOuterHolder .newWindow").attr("target", "_blank");
		jQuery("#MetatagOuterHolder .actions ul, #MetatagOuterHolder  tr.subsequentActions").hide();

	},




	// pre-submit callback
	showRequest: function(formData, jqForm, options) {
		jQuery('body').addClass("loading");
		// formData is an array; here we use jQuery.param to convert it to a string to display it
		// but the form plugin does this for you automatically when it submits the data
		var queryString = jQuery.param(formData);

		// jqForm is a jQuery object encapsulating the form element.  To access the
		// DOM element for the form do shiothis:
		// var formElement = jqForm[0];
		//alert('About to submit: \n\n' + queryString);
		return true;
	},

	// post-submit callback
	showResponse: function (responseText, statusText, xhr, jQueryform)  {
		jQuery('body').removeClass("loading");
		// for normal html responses, the first argument to the success callback
		// is the XMLHttpRequest object's responseText property

		// if the ajaxForm method was passed an Options Object with the dataType
		// property set to 'xml' then the first argument to the success callback
		// is the XMLHttpRequest object's responseXML property

		// if the ajaxForm method was passed an Options Object with the dataType
		// property set to 'json' then the first argument to the success callback
		// is the json data object returned by the server

		//alert('status: ' + statusText + '\n\nresponseText: \n' + responseText + '\n\nThe output div should have already been updated with the responseText.');
	},

	// post-submit callback
	showError: function (XMLHttpRequest)  {
		jQuery('body').removeClass("loading");
		alert("ERROR: Update unsuccessful.");
		// for normal html responses, the first argument to the success callback
		// is the XMLHttpRequest object's responseText property

		// if the ajaxForm method was passed an Options Object with the dataType
		// property set to 'xml' then the first argument to the success callback
		// is the XMLHttpRequest object's responseXML property

		// if the ajaxForm method was passed an Options Object with the dataType
		// property set to 'json' then the first argument to the success callback
		// is the json data object returned by the server
	},

	fixSerialize: function ($form, options) {
		//alert(MetaTagCMSControl.fieldName);
		jQuery("#FieldName").attr("value",MetaTagCMSControl.fieldName);
	}


}

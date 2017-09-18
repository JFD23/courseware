define(['assets/js/student_view', 'assets/js/url'],
       function (StudentView, helper) {

    'use strict';

    return StudentView.extend({
        events: {
            "click button[name=save]":   "onSave",
        },

        initialize: function() {
            var $section = this.$el.closest('section.PortfolioBlockSupervisor');
            var $sortingButtons = jQuery('button.lower', $section);
            $sortingButtons = $sortingButtons.add(jQuery('button.raise', $section));
            $sortingButtons.removeClass('no-sorting');
        },

        render: function() {
            return this;
        },

        postRender: function () {
            MathJax.Hub.Queue(["Typeset", MathJax.Hub, this.el]);
        },
        onSave: function (event) {
            var textarea = this.$("textarea"),
                new_val = textarea.val(),
                view = this;

            //textarea.remove();
            helper
                .callHandler(this.model.id, "savesupervisor", {supervisorcontent: new_val})
                .then(
                    // success
                    function () {
                        jQuery(event.target).addClass("accept");
                    },

                    // error
                    function (error) {
                        var errorMessage = 'Could not update the block: '+jQuery.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    })
                .done();
        }
    });
});

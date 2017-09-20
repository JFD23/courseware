define(['assets/js/student_view', 'assets/js/url', 'assets/js/templates'],
       function (StudentView, helper, templates) {

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
                    function (resp) {
                        jQuery(event.target).addClass("accept");
                        view.model.set('content', resp.content);
                        view.model.set('supervisorcontent', resp.supervisorcontent);
                        view.model.set('show_note', true);
                        view.model.set('supervisor', true);
                        view.model.set('supervisorcontentstored', true);
                        view.$el.html(templates("PortfolioBlockSupervisor", 'student_view', _.clone(view.model.attributes)));
                        view.$(".supervisorcontentstored").delay(2000).slideUp();
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

import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'

export default StudentView.extend({
    events: {
    },

    initialize() {
        var $section = this.$el.closest('section.PortfolioBlockUser');
        var $sortingButtons = jQuery('button.lower', $section);
        $sortingButtons = $sortingButtons.add(jQuery('button.raise', $section));
        $sortingButtons.removeClass('no-sorting');
    },

    render() {
        return this;
    },

    postRender() {
        MathJax.Hub.Queue(["Typeset", MathJax.Hub, this.el]);
    }
});

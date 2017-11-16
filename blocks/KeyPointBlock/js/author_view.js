import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

  events: {
    'click button[name=save]':   'onSave',
    'click button[name=cancel]': 'switchBack',
  },

  initialize() {
    Backbone.on('beforemodeswitch', this.onModeSwitch, this);
    Backbone.on('beforenavigate', this.onNavigate, this);
  },

  render() {
    return this;
  },

  postRender() {
      var $view = this;
    if($view.$(".cw-keypoint-stored-icon").val() != "") {
        $view.$('select.cw-keypoint-select-icon option[value="'+$view.$('.cw-keypoint-stored-icon').val()+'"]').prop('selected', true);
    }
    if($view.$(".cw-keypoint-stored-color").val() != "") {
        $view.$('select.cw-keypoint-select-color option[value="'+$view.$('.cw-keypoint-stored-color').val()+'"]').prop('selected', true);
    }

    return this;
  },

  onNavigate(event) {
    if (!$('section .block-content button[name=save]').length) {
      return;
    }
    if(event.isUserInputHandled) {
      return;
    }
    event.isUserInputHandled = true;
    Backbone.trigger('preventnavigateto', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie die Seite trotzdem verlassen?'));
  },

  onModeSwitch(toView, event) {
    if (toView != 'student') {
      return;
    }
    // the user already switched back (i.e. the is not visible)
    if (!this.$el.is(':visible')) {
      return;
    }
    // another listener already handled the user's feedback
    if (event.isUserInputHandled) {
      return;
    }
    event.isUserInputHandled = true;
    Backbone.trigger('preventviewswitch', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie trotzdem fortfahren?'));
  },

  onSave(event) {
    var $view = this;
    var $keypoint_content = $view.$('.cw-keypoint-set-content').val();
    var $keypoint_color = $view.$('select.cw-keypoint-select-color option:selected').val();
    var $keypoint_icon = $view.$('select.cw-keypoint-select-icon option:selected').val();

    helper
      .callHandler(this.model.id, 'save', {
        keypoint_content: $keypoint_content,
        keypoint_color: $keypoint_color,
        keypoint_icon: $keypoint_icon
      })
      .then(
        // success
        function () {
          $(event.target).addClass('accept');
          $view.switchBack();
        },

        // error
        function (error) {
          var errorMessage = 'Could not update the block: '+$.parseJSON(error.responseText).reason;
          alert(errorMessage);
          console.log(errorMessage, arguments);
        });
  }
});
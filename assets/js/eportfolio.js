$(document).ready(function() {
  animationTooltip();
  console.log("hello");
  $('.cktoolbar').css({'width': '100%', 'max-width': '100%'});

});



function animationTooltip() {

  console.log("it is logged");

  $('#eportfolioblock-block').hover(
    function() {
      $('#eportfolioblock-tooltip').addClass('show animated flipInX');
    },
    function() {
      $('#eportfolioblock-tooltip').removeClass('show animated flipInX');
    }
  );

  $('#eportfolioblocksupervisor-block').hover(
    function() {
      $('#eportfolioblocksupervisor-tooltip').addClass('show animated flipInX');
    },
    function() {
      $('#eportfolioblocksupervisor-tooltip').removeClass('show animated flipInX');
    }
  );

  $('#eportfolioblockuser-block').hover(
    function() {
      $('#eportfolioblockuser-tooltip').addClass('show animated flipInX');
    },
    function() {
      $('#eportfolioblockuser-tooltip').removeClass('show animated flipInX');
    }
  );

};

jQuery(document.body).ready(function ($) {
  'use strict'

  const $buttons = $('a[class^="tally-search-results-"]')
  $buttons.on('click', function (e) {
    e.preventDefault()

    $buttons.toggleClass('active')
    const types = ['plugins', 'themes']

    types.forEach((type) => {
      let $target = $('div[class="tally-search-results-' + type + '"]')
      if ($target.is(':visible')) {
        $target.hide()
      } else {
        $target.show()
      }
    });
  })
})

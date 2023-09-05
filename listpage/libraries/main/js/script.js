(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.listpage_main = {
    attach(context, settings) {

      // Building the datatable on document load.
      if (context == document) {
        let table = $("table.legislation-data-table").DataTable({
          searching: false,
          ordering: false,
          lengthChange: false,
          serverSide: true,
          pagingType: "simple",
          processing: true,
          info: false,
          scrollCollapse: true,
          scrollY: '50vh',
          ajax: {
            url: "/fetch-legislation-data",
          },
          drawCallback: function (settings) {
            let data = settings.json;
            console.log(data);
            // return if no links are present.
            if (!data.links) return;

            // If links are present update the buttons with dynamic pagination link from server.
            if ("next" in data.links) {
              let nextButtonElement = $("a.paginate_button.next");
              nextButtonElement.removeClass("disabled");
              nextButtonElement.off('click').click(() => {
                table.clear().ajax.url(`/fetch-legislation-data?${data.links.next}`).load();
              });
            }
            if ("prev" in data.links) {
              let prevButtonElement = $("a.paginate_button.previous");
              prevButtonElement.removeClass("disabled");
              prevButtonElement.off('click').click(() => {
                table.clear().ajax.url(`/fetch-legislation-data?${data.links.prev}`).load();
              });
            }
          },
          preDrawCallback: function (settings) {
            $("a.paginate_button.next").addClass("disabled");
            $("a.paginate_button.previous").addClass("disabled");
          }
        });
      }
    }
  }

})(jQuery, Drupal, drupalSettings)
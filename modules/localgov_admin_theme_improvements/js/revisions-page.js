(function localgovRevisionsPageScript(Drupal) {
  Drupal.behaviors.localgovRevisionsPage = {
    attach(context) {

      const compareRevisionsButton = context.querySelector('.diff-button');
      const revisionsForm = context.querySelector('#revision-overview-form');

      if (compareRevisionsButton && revisionsForm) {
        const htmlToInsert = compareRevisionsButton.outerHTML;
        const newButtonContainer = document.createElement('div');
        newButtonContainer.innerHTML = htmlToInsert;

        // Insert the compare button into the form, just inside the form element.
        revisionsForm.insertBefore(newButtonContainer, revisionsForm.firstChild);
      }

    }
  };
})(Drupal);

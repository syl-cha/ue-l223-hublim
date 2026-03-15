import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form']

    connect() {
    }

    toggle(event) {
        if (event) {
            event.preventDefault();
        }
        this.formTarget.classList.toggle('d-none');
    }
}

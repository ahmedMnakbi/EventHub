import { Controller } from '@hotwired/stimulus';

/*
 * Asks for confirmation before submitting a form (used on delete buttons).
 * Usage: <form data-controller="confirm" data-action="confirm#ask" data-confirm-message-value="...">
 * A Stimulus controller is used instead of an inline onsubmit="" so the page stays
 * compatible with a strict Content-Security-Policy.
 */
export default class extends Controller {
    static values = { message: { type: String, default: 'Confirmer cette action ?' } };

    ask(event) {
        if (!window.confirm(this.messageValue)) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }
}

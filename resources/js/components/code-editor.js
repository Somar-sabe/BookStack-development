import {onChildEvent, onEnterPress, onSelect} from "../services/dom";
import {Component} from "./component";


export class CodeEditor extends Component {

    setup() {
        this.container = this.$refs.container;
        this.popup = this.$el;
        this.editorInput = this.$refs.editor;
        this.languageButtons = this.$manyRefs.languageButton;
        this.languageOptionsContainer = this.$refs.languageOptionsContainer;
        this.saveButton = this.$refs.saveButton;
        this.languageInput = this.$refs.languageInput;
        this.historyDropDown = this.$refs.historyDropDown;
        this.historyList = this.$refs.historyList;
        this.favourites = new Set(this.$opts.favourites.split(','));

        this.callback = null;
        this.editor = null;
        this.history = {};
        this.historyKey = 'code_history';
        this.setupListeners();
        this.setupFavourites();
    }

    setupListeners() {
        this.container.addEventListener('keydown', event => {
            if (event.ctrlKey && event.key === 'Enter') {
                this.save();
            }
        });

        onSelect(this.languageButtons, event => {
            const language = event.target.dataset.lang;
            this.languageInput.value = language;
            this.languageInputChange(language);
        });

        onEnterPress(this.languageInput, e => this.save());
        this.languageInput.addEventListener('input', e => this.languageInputChange(this.languageInput.value));
        onSelect(this.saveButton, e => this.save());

        onChildEvent(this.historyList, 'button', 'click', (event, elem) => {
            event.preventDefault();
            const historyTime = elem.dataset.time;
            if (this.editor) {
                this.editor.setValue(this.history[historyTime]);
            }
        });
    }

    setupFavourites() {
        for (const button of this.languageButtons) {
            this.setupFavouritesForButton(button);
        }

        this.sortLanguageList();
    }

    /**
     * @param {HTMLButtonElement} button
     */
    setupFavouritesForButton(button) {
        const language = button.dataset.lang;
        let isFavorite = this.favourites.has(language);
        button.setAttribute('data-favourite', isFavorite ? 'true' : 'false');

        onChildEvent(button.parentElement, '.lang-option-favorite-toggle', 'click', () => {
            isFavorite = !isFavorite;
            isFavorite ? this.favourites.add(language) : this.favourites.delete(language);
            button.setAttribute('data-favourite', isFavorite ? 'true' : 'false');

            window.$http.patch('/preferences/update-code-language-favourite', {
                language: language,
                active: isFavorite
            });

            this.sortLanguageList();
            if (isFavorite) {
                button.scrollIntoView({block: "center", behavior: "smooth"});
            }
        });
    }

    sortLanguageList() {
        const sortedParents = this.languageButtons.sort((a, b) => {
            const aFav = a.dataset.favourite === 'true';
            const bFav = b.dataset.favourite === 'true';

            if (aFav && !bFav) {
                return -1;
            } else if (bFav && !aFav) {
                return 1;
            }

            return a.dataset.lang > b.dataset.lang ? 1 : -1;
        }).map(button => button.parentElement);

        for (const parent of sortedParents) {
            this.languageOptionsContainer.append(parent);
        }
    }

    save() {
        if (this.callback) {
            this.callback(this.editor.getValue(), this.languageInput.value);
        }
        this.hide();
    }

    open(code, language, callback) {
        this.languageInput.value = language;
        this.callback = callback;

        this.show()
            .then(() => this.languageInputChange(language))
            .then(() => window.importVersioned('code'))
            .then(Code => Code.setContent(this.editor, code));
    }

    async show() {
        const Code = await window.importVersioned('code');
        if (!this.editor) {
            this.editor = Code.popupEditor(this.editorInput, this.languageInput.value);
        }

        this.loadHistory();
        this.getPopup().show(() => {
            Code.updateLayout(this.editor);
            this.editor.focus();
        }, () => {
            this.addHistory()
        });
    }

    hide() {
        this.getPopup().hide();
        this.addHistory();
    }

    /**
     * @returns {Popup}
     */
    getPopup() {
        return window.$components.firstOnElement(this.popup, 'popup');
    }

    async updateEditorMode(language) {
        const Code = await window.importVersioned('code');
        Code.setMode(this.editor, language, this.editor.getValue());
    }

    languageInputChange(language) {
        this.updateEditorMode(language);
        const inputLang = language.toLowerCase();

        for (const link of this.languageButtons) {
            const lang = link.dataset.lang.toLowerCase().trim();
            const isMatch = inputLang === lang;
            link.classList.toggle('active', isMatch);
            if (isMatch) {
                link.scrollIntoView({block: "center", behavior: "smooth"});
            }
        }
    }

    loadHistory() {
        this.history = JSON.parse(window.sessionStorage.getItem(this.historyKey) || '{}');
        const historyKeys = Object.keys(this.history).reverse();
        this.historyDropDown.classList.toggle('hidden', historyKeys.length === 0);
        this.historyList.innerHTML = historyKeys.map(key => {
             const localTime = (new Date(parseInt(key))).toLocaleTimeString();
             return `<li><button type="button" data-time="${key}" class="text-item">${localTime}</button></li>`;
        }).join('');
    }

    addHistory() {
        if (!this.editor) return;
        const code = this.editor.getValue();
        if (!code) return;

        // Stop if we'd be storing the same as the last item
        const lastHistoryKey = Object.keys(this.history).pop();
        if (this.history[lastHistoryKey] === code) return;

        this.history[String(Date.now())] = code;
        const historyString = JSON.stringify(this.history);
        window.sessionStorage.setItem(this.historyKey, historyString);
    }

}
import { EditorView, basicSetup } from "codemirror";
import { javascript } from "@codemirror/lang-javascript";
import { sass } from "@codemirror/lang-sass";
import { keymap } from "@codemirror/view";
import { oneDark } from "@codemirror/theme-one-dark";
import beautify from "js-beautify";
import { parse } from "scss-parser";
import * as acorn from "acorn";
import "../scss/style.scss";

(function (Drupal) {
  Drupal.behaviors.layout_custom_style_codemirror = {
    attach: function (context, settings) {
      // Vérification syntaxique basique
      // function checkBasicSyntax(code, language) {
      //   const errors = [];
      //   const stack = [];
      //   const pairs = { "{": "}", "(": ")", "[": "]" };
      //   const opening = Object.keys(pairs);
      //   const closing = Object.values(pairs);

      //   for (let i = 0; i < code.length; i++) {
      //     const char = code[i];
      //     if (opening.includes(char)) {
      //       stack.push({ char, pos: i });
      //     } else if (closing.includes(char)) {
      //       const last = stack.pop();
      //       if (!last || pairs[last.char] !== char) {
      //         errors.push(`Déséquilibre à la position ${i}: '${char}' inattendu.`);
      //       }
      //     }
      //   }

      //   stack.forEach((item) => {
      //     errors.push(`Ouverture '${item.char}' non fermée à la position ${item.pos}.`);
      //   });
      //   return errors;
      // }
      function validateJS(code) {
        try {
          acorn.parse(code, { ecmaVersion: "latest" });
          return []; // aucune erreur
        } catch (error) {
          // error.message contient ligne/colonne (ex: "Unexpected token (5:12)")
          return [error.message];
        }
      }
      function validateSCSS(code) {
        try {
          parse(code);
          return [];
        } catch (error) {
          // error.message contient généralement ligne/colonne
          return [error.message];
        }
      }

      function formatCode(view, language) {
        const formatBtn = view.dom.parentNode.querySelector(".codemirror-format-btn");
        const originalHTML = formatBtn.innerHTML;
        formatBtn.innerHTML = "⏳";
        formatBtn.disabled = true;

        try {
          const code = view.state.doc.toString();
          let errors = [];
          // Vérification syntaxique
          if (language === "js") {
            errors = validateJS(code);
          } else if (language === "scss") {
            errors = validateSCSS(code);
          } else {
            alert("Langage non supporté");
            return;
          }
          if (errors.length > 0) {
            console.log("errors : ", errors);
            const firstError = errors[0];
            const otherCount = errors.length - 1;
            let message = `⚠️ ${firstError}`;
            if (otherCount > 0) {
              message += ` (et ${otherCount} autre${otherCount > 1 ? "s" : ""})`;
            }
            // Afficher le message d'erreur dans la barre d'état
            const wrapper = view.dom.closest(".codemirror-wrapper");
            const statusSpan = wrapper.querySelector(".codemirror-status");
            statusSpan.textContent = message;
            statusSpan.title = errors.join("\n");
            return;
          }
          let formatted;
          if (language === "js") {
            formatted = beautify.js(code, {
              indent_size: 2,
              space_in_empty_paren: true,
              jslint_happy: true,
              max_preserve_newlines: 2,
              end_with_newline: true,
              newline_between_rules: false,
            });
          } else if (language === "scss") {
            // SCSS est un sur-ensemble de CSS, donc le formateur CSS fonctionne bien
            formatted = beautify.css(code, {
              indent_size: 2,
              space_around_combinator: true,
              max_preserve_newlines: 2,
              end_with_newline: true,
              newline_between_rules: false,
            });
          } else {
            alert("Langage non supporté");
            return;
          }

          view.dispatch({
            changes: { from: 0, to: view.state.doc.length, insert: formatted },
          });
        } catch (error) {
          console.error("Erreur de formatage :", error);
          alert("Erreur : " + error.message);
        } finally {
          formatBtn.innerHTML = originalHTML;
          formatBtn.disabled = false;
        }
      }
      // Fonction pour basculer le plein écran
      function toggleFullScreen(view) {
        const wrapper = view.dom.closest(".codemirror-wrapper");
        if (wrapper) {
          wrapper.classList.toggle("fullscreen");
          // Mettre à jour le bouton
          const btn = wrapper.querySelector(".codemirror-fullscreen-btn");
          if (btn) {
            btn.classList.toggle("active"); // classe active pour styler le bouton
            // Optionnel : changer l'icône (ex: ⛶ ↔ ✕)
            btn.innerHTML = wrapper.classList.contains("fullscreen") ? "✕" : "⛶";
          }
          setTimeout(() => view.requestMeasure(), 50);
        }
      }

      // Extension keymap pour F11 et Echap (optionnel mais utile)
      const fullScreenKeymap = keymap.of([
        {
          key: "F11",
          run: (view) => {
            toggleFullScreen(view);
            return true;
          },
        },
        {
          key: "Escape",
          run: (view) => {
            if (view.dom.classList.contains("fullscreen")) {
              toggleFullScreen(view);
              return true;
            }
            return false;
          },
        },
      ]);

      /**
       * Crée un éditeur à partir d'un textarea avec un bouton plein écran
       */
      const editorFromTextArea = (textarea, extensions) => {
        // Créer le conteneur
        const wrapper = document.createElement("div");
        wrapper.className = "codemirror-wrapper";
        textarea.parentNode.insertBefore(wrapper, textarea);

        // Créer la barre d'outils avec le bouton
        const toolbar = document.createElement("div");
        toolbar.className = "codemirror-toolbar";
        const fullscreenBtn = document.createElement("button");
        fullscreenBtn.type = "button";
        fullscreenBtn.textContent = "⛶";
        fullscreenBtn.className = "codemirror-fullscreen-btn btn";
        fullscreenBtn.title = "Plein écran (F11)";
        toolbar.appendChild(fullscreenBtn);
        // Bouton formatage
        const formatBtn = document.createElement("button");
        formatBtn.type = "button";
        formatBtn.innerHTML = "🧹";
        formatBtn.className = "codemirror-btn codemirror-format-btn btn";
        formatBtn.title = "Formater le code avec Prettier";
        toolbar.appendChild(formatBtn);
        // Dans editorFromTextArea, après avoir ajouté les boutons
        const statusSpan = document.createElement("div");
        statusSpan.className = "codemirror-status";
        toolbar.appendChild(statusSpan);
        // Insérer la toolbar dans le wrapper
        wrapper.appendChild(toolbar);

        // Créer l'éditeur
        const view = new EditorView({
          doc: textarea.value,
          extensions: [basicSetup, fullScreenKeymap, oneDark, ...extensions],
        });

        // Insérer l'éditeur après la toolbar
        wrapper.appendChild(view.dom);

        // Cacher le textarea
        textarea.style.display = "none";

        // Lier le bouton à la fonction toggleFullScreen
        fullscreenBtn.addEventListener("click", (event) => {
          event.stopPropagation();
          toggleFullScreen(view);
        });

        // Événement formatage
        formatBtn.addEventListener("click", () => {
          const lang = textarea.classList.contains("lang_js") ? "js" : textarea.classList.contains("lang_scss") ? "scss" : null;
          if (lang) {
            formatCode(view, lang);
          } else {
            alert("Langage non supporté pour le formatage");
          }
        });

        // Mettre à jour le textarea avant soumission
        if (textarea.form) {
          textarea.form.addEventListener("submit", () => {
            textarea.value = view.state.doc.toString();
          });
        }

        // Pour debug
        window.current_mirror_editor = view;
        return view;
      };

      // Le reste est identique : addEditorOnTextarea et la gestion des details
      const addEditorOnTextarea = (textarea) => {
        if (!textarea.classList.contains("code_mirror_loaded")) {
          textarea.classList.add("code_mirror_loaded");
          let langExt = [];
          if (textarea.classList.contains("lang_js")) {
            langExt = [javascript()];
          } else if (textarea.classList.contains("lang_scss")) {
            langExt = [sass()];
          }
          editorFromTextArea(textarea, langExt);
        }
      };

      // Sélection et initialisation (inchangé)
      if (context.querySelectorAll && context.querySelectorAll("textarea.codemirror").length) {
        const textareas = context.querySelectorAll("textarea.codemirror");
        textareas.forEach((textarea) => {
          const details = textarea.closest("details");
          if (details) {
            details.addEventListener("toggle", () => {
              setTimeout(() => {
                if (details.hasAttribute("open")) {
                  addEditorOnTextarea(textarea);
                }
              }, 700);
            });
          } else {
            addEditorOnTextarea(textarea);
          }
        });
      }
    },
  };
})(window.Drupal);

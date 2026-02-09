import { EditorView, basicSetup } from "codemirror"
import { lineNumbers, highlightSpecialChars, drawSelection } from "@codemirror/view"
import { json } from "@codemirror/lang-json"
import { Compartment, EditorState } from "@codemirror/state"
import { HighlightStyle, syntaxHighlighting } from "@codemirror/language"
import { tags as t } from "@lezer/highlight"

const themeCompartment = new Compartment()
const syntaxCompartment = new Compartment()
const readOnlyCompartment = new Compartment()

// --- Colores Modo Oscuro (#0f0f0f) ---
const darkHighlight = HighlightStyle.define([
    { tag: t.propertyName, color: "#38bdf8" },
    { tag: t.string, color: "#fb923c" },
    { tag: t.number, color: "#a855f7" },
    { tag: t.bool, color: "#2dd4bf" },
    { tag: t.null, color: "#2dd4bf" },
    { tag: t.separator, color: "#9ca3af" },
    { tag: t.brace, color: "#9ca3af" }
])

// --- Colores Modo Claro (Contraste Alto) ---
const lightHighlight = HighlightStyle.define([
    { tag: t.propertyName, color: "#0369a1" },
    { tag: t.string, color: "#c2410c" },
    { tag: t.number, color: "#7e22ce" },
    { tag: t.bool, color: "#0f766e" },
    { tag: t.null, color: "#0f766e" },
    { tag: t.separator, color: "#4b5563" },
    { tag: t.brace, color: "#4b5563" }
])

const vaxtlyDark = EditorView.theme({
    "&": { backgroundColor: "#0f0f0f", color: "#e5e7eb" },
    ".cm-content": { caretColor: "#38bdf8" },
    "&.cm-focused .cm-cursor": { borderLeftColor: "#38bdf8" },
    "&.cm-focused .cm-selectionBackground, .cm-selectionBackground, .cm-content ::selection": { backgroundColor: "#0c4a6e" },
    ".cm-gutters": { backgroundColor: "#0f0f0f", color: "#4b5563", border: "none" }
}, { dark: true })

const vaxtlyLight = EditorView.theme({
    "&": { backgroundColor: "#ffffff", color: "#111827" },
    ".cm-content": { caretColor: "#0284c7" },
    "&.cm-focused .cm-cursor": { borderLeftColor: "#0284c7" },
    "&.cm-focused .cm-selectionBackground, .cm-selectionBackground, .cm-content ::selection": { backgroundColor: "#bae6fd" },
    ".cm-gutters": { backgroundColor: "#f9fafb", color: "#6b7280", borderRight: "1px solid #e5e7eb" }
}, { dark: false })

window.setupJsonEditor = (element, content, onChange, isDark, isReadOnly = false) => {
    const view = new EditorView({
        doc: content,
        extensions: [
            basicSetup,
            json(),
            EditorView.lineWrapping,
            themeCompartment.of(isDark ? vaxtlyDark : vaxtlyLight),
            syntaxCompartment.of(syntaxHighlighting(isDark ? darkHighlight : lightHighlight)),
            readOnlyCompartment.of([
                EditorState.readOnly.of(isReadOnly),
                isReadOnly ? EditorView.editable.of(false) : []
            ]),
            EditorView.updateListener.of((update) => {
                if (update.docChanged) onChange(update.state.doc.toString());
            }),
        ],
        parent: element,
    });

    view.updateTheme = (isDark) => {
        view.dispatch({
            effects: [
                themeCompartment.reconfigure(isDark ? vaxtlyDark : vaxtlyLight),
                syntaxCompartment.reconfigure(syntaxHighlighting(isDark ? darkHighlight : lightHighlight))
            ]
        });
    }

    view.setReadOnly = (readOnly) => {
        view.dispatch({
            effects: readOnlyCompartment.reconfigure([
                EditorState.readOnly.of(readOnly),
                readOnly ? EditorView.editable.of(false) : []
            ])
        });
    }

    return view;
}

window.setupJsonViewer = (element, content, onChange, isDark) => {
    const view = new EditorView({
        doc: content,
        extensions: [
            lineNumbers(),
            highlightSpecialChars(),
            drawSelection(),
            json(),
            EditorView.lineWrapping,
            themeCompartment.of(isDark ? vaxtlyDark : vaxtlyLight),
            syntaxCompartment.of(syntaxHighlighting(isDark ? darkHighlight : lightHighlight)),
            EditorState.readOnly.of(true),
            EditorView.editable.of(false),
        ],
        parent: element,
    });

    view.updateTheme = (isDark) => {
        view.dispatch({
            effects: [
                themeCompartment.reconfigure(isDark ? vaxtlyDark : vaxtlyLight),
                syntaxCompartment.reconfigure(syntaxHighlighting(isDark ? darkHighlight : lightHighlight))
            ]
        });
    }

    return view;
}
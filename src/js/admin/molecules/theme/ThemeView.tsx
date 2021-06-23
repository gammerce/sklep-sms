import React, { FunctionComponent, useEffect, useState } from "react";
import Select from "react-select";
import Creatable from "react-select/creatable";
import { Controlled as CodeMirror } from "react-codemirror2";
import "codemirror/mode/htmlmixed/htmlmixed";
import { api } from "../../utils/container";
import { handleError } from "../../../shop/utils/utils";
import { infobox } from "../../../general/infobox";
import { __ } from "../../../general/i18n";
import { onKeyPress, toggleLoader } from "../../../general/effects";
import classNames from "classnames";
import { Lang } from "../../types/template";

interface SelectOption {
    label: string;
    value: string;
}

interface TemplateSelectOption {
    label: string;
    value: string;
    deletable: boolean;
}

export const ThemeView: FunctionComponent = () => {
    const [templateList, setTemplateList] = useState<TemplateSelectOption[]>([]);
    const [templateListLoading, setTemplateListLoading] = useState<boolean>(false);
    const [selectedTemplate, setSelectedTemplate] = useState<TemplateSelectOption | null>(null);
    const [fetchedTemplateContent, setFetchedTemplateContent] = useState<string>("");
    const [templateContent, setTemplateContent] = useState<string>("");

    const [themeList, setThemeList] = useState<SelectOption[]>();
    const [themeListLoading, setThemeListLoading] = useState<boolean>(false);
    const [selectedTheme, setSelectedTheme] = useState<SelectOption | null>(null);

    const [selectedLang, setSelectedLang] = useState<SelectOption | null>(null);
    const languageList = ["pl", "en"].map((val) => ({ label: val, value: val }));

    const [templateLoading, setTemplateLoading] = useState<boolean>(false);
    const [updating, setUpdating] = useState<boolean>(false);
    const [resetting, setResetting] = useState<boolean>(false);

    const areChangesUnsaved = fetchedTemplateContent !== templateContent;

    const loadThemeList = async () => {
        try {
            setThemeListLoading(true);
            const response = await api.getThemeList();
            const options = response.data.map((theme) => ({
                label: theme.name,
                value: theme.name,
            }));
            setThemeList(options);
        } catch (e) {
            infobox.showError(e.toString());
        } finally {
            setThemeListLoading(false);
        }
    };

    const loadTemplateList = async (theme: string, lang: Lang) => {
        try {
            setTemplateListLoading(true);
            const response = await api.getTemplateList(theme, lang);
            const options = response.data.map((template) => ({
                label: template.name,
                value: template.name,
                deletable: template.deletable,
            }));
            setTemplateList(options);
        } catch (e) {
            infobox.showError(e.toString());
        } finally {
            setTemplateListLoading(false);
        }
    };

    const loadTemplate = async (theme: string, name: string, lang: Lang) => {
        try {
            setTemplateLoading(true);
            const response = await api.getTemplate(theme, name, lang);
            setTemplateContent(response.content);
            setFetchedTemplateContent(response.content);
        } catch (e) {
            infobox.showError(e.toString());
        } finally {
            setTemplateLoading(false);
        }
    };

    const updateTemplate = async () => {
        if (!areChangesUnsaved) {
            return;
        }

        try {
            setUpdating(true);
            await api.putTemplate(
                selectedTheme.value,
                selectedTemplate.value,
                selectedLang?.value ?? null,
                templateContent
            );
            setFetchedTemplateContent(templateContent);
            changeDeletabilityTo(true);
            infobox.showSuccess(__("template_updated"));
        } catch (e) {
            infobox.showError(e.toString());
        } finally {
            setUpdating(false);
        }
    };

    const resetTemplate = async () => {
        const theme = selectedTheme.value;
        const name = selectedTemplate.value;
        const lang = selectedLang?.value ?? null;

        if (!confirm(__("reset_template_confirmation", name, theme))) {
            return;
        }

        try {
            setResetting(true);
            await api.deleteTemplate(theme, name, lang);
            await loadTemplate(theme, name, lang);
            changeDeletabilityTo(false);
            infobox.showSuccess(__("template_reset"));
        } catch (e) {
            infobox.showError(e.toString());
        } finally {
            setResetting(false);
        }
    };

    const changeDeletabilityTo = (value: boolean): void => {
        // Don't change deletability to a current value
        if (selectedTemplate.deletable === value) {
            return;
        }

        const newTemplateList = templateList.map((t) => {
            if (t.value == selectedTemplate.value) {
                return { ...t, deletable: value };
            }

            return t;
        });
        setTemplateList(newTemplateList);
    };

    const handleThemeChange = (selectedOption: SelectOption | null) => {
        if (areChangesUnsaved && !confirm(__("template_unsaved_changes_confirmation"))) {
            return;
        }

        setSelectedTheme(selectedOption);
    };

    const handleTemplateChange = (selectedOption: TemplateSelectOption | null) => {
        if (areChangesUnsaved && !confirm(__("template_unsaved_changes_confirmation"))) {
            return;
        }

        setSelectedTemplate(selectedOption);
    };

    const handleLangChange = (selectedOption: SelectOption | null) => {
        if (areChangesUnsaved && !confirm(__("template_unsaved_changes_confirmation"))) {
            return;
        }

        setSelectedLang(selectedOption);
    };

    const handleTemplateContentChange = (editor, data, value) => setTemplateContent(value);

    useEffect(() => {
        loadThemeList().catch(handleError);
    }, []);

    // Reload templates on theme change
    useEffect(() => {
        if (selectedTheme) {
            loadTemplateList(selectedTheme.value, selectedLang?.value ?? null).catch(handleError);
        } else {
            setTemplateList([]);
            setSelectedTemplate(null);
        }
    }, [selectedTheme]);

    // Load template on theme/template/language change
    useEffect(() => {
        if (selectedTheme && selectedTemplate) {
            loadTemplate(
                selectedTheme.value,
                selectedTemplate.value,
                selectedLang?.value ?? null
            ).catch(handleError);
        } else {
            setTemplateContent("");
            setFetchedTemplateContent("");
        }
    }, [selectedTheme, selectedTemplate?.value, selectedLang]);

    // Handle saving using ctrl + s
    useEffect(() => onKeyPress((e) => (e.ctrlKey || e.metaKey) && e.key == "s", updateTemplate), [
        templateContent,
    ]);

    // Update selected template
    useEffect(() => {
        if (selectedTemplate) {
            const newTemplate = templateList.find((t) => t.value === selectedTemplate.value);
            setSelectedTemplate(newTemplate);
        }
    }, [templateList]);

    // Set template if query is passed
    useEffect(() => {
        if (selectedTemplate) {
            return;
        }

        // TODO Theme needs to be selected as well
        // TODO Think about nullable theme

        const searchParams = new URLSearchParams(window.location.search);
        const templateName = searchParams.get("name");
        if (!templateName) {
            return;
        }

        const template = templateList.find((t) => t.value == templateName);
        if (template) {
            history.replaceState("theme", document.title, window.location.pathname);
            setSelectedTemplate(template);
        }
    }, [templateList]);

    // Display loader
    useEffect(() => toggleLoader(templateLoading), [templateLoading]);

    const templateStyles = {
        option: (styles, data) => {
            const selectOption: TemplateSelectOption = data.data;

            return {
                ...styles,
                fontWeight: selectOption.deletable ? "bold" : "normal",
            };
        },
    };

    return (
        <>
            <div className="subtitle">
                Dostosuj wygląd sklepu poprzez edycję szablonów HTML. Wybierz motyw z listy lub
                stwórz nowy wpisując wybraną przez siebie nazwę.
            </div>

            <div className="field is-grouped">
                <div className="control theme-control">
                    <Creatable
                        className="theme-selector"
                        options={themeList}
                        value={selectedTheme}
                        placeholder={__("select_theme")}
                        onChange={handleThemeChange}
                        isLoading={themeListLoading}
                        isClearable
                    />
                </div>

                <div className="control is-expanded">
                    <Select
                        className="template-selector"
                        options={templateList}
                        value={selectedTemplate}
                        placeholder={__("select_template")}
                        styles={templateStyles}
                        onChange={handleTemplateChange}
                        isLoading={templateListLoading}
                        isClearable
                    />
                </div>

                <div className="control">
                    <Select
                        className="lang-selector"
                        options={languageList}
                        value={selectedLang}
                        placeholder={__("select_language")}
                        onChange={handleLangChange}
                        isClearable
                    />
                </div>

                <div className="control">
                    <p className="buttons">
                        <button
                            className={classNames(["button is-success"], {
                                "is-loading": updating,
                            })}
                            disabled={!areChangesUnsaved}
                            onClick={updateTemplate}
                        >
                            {__("save")}
                        </button>

                        <button
                            className={classNames(["button is-danger"], {
                                "is-loading": resetting,
                            })}
                            disabled={!selectedTemplate?.deletable}
                            onClick={resetTemplate}
                        >
                            {__("reset")}
                        </button>
                    </p>
                </div>
            </div>

            <CodeMirror
                value={templateContent}
                options={{
                    mode: "htmlmixed",
                    theme: "material",
                    lineNumbers: true,
                    readOnly: selectedTemplate ? false : "nocursor",
                }}
                onBeforeChange={handleTemplateContentChange}
            />
        </>
    );
};

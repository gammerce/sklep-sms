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

interface SelectOption {
    label: string;
    value: string;
}

interface TemplateSelectOption {
    label: string;
    value: string;
    deletable: boolean;
}

// TODO Fix reset button activity

export const ThemeView: FunctionComponent = () => {
    const [templateList, setTemplateList] = useState<TemplateSelectOption[]>([]);
    const [templateListLoading, setTemplateListLoading] = useState<boolean>(true);
    const [selectedTemplate, setSelectedTemplate] = useState<TemplateSelectOption>();
    const [fetchedTemplateContent, setFetchedTemplateContent] = useState<string>("");
    const [templateContent, setTemplateContent] = useState<string>("");

    const [themeList, setThemeList] = useState<SelectOption[]>();
    const [themeListLoading, setThemeListLoading] = useState<boolean>(true);
    const [selectedTheme, setSelectedTheme] = useState<SelectOption>({
        label: "fusion",
        value: "fusion",
    });

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

    const loadTemplateList = async (theme: string) => {
        try {
            setTemplateListLoading(true);
            const response = await api.getTemplateList(theme);
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

    const loadTemplate = async (theme: string, name: string) => {
        try {
            setTemplateLoading(true);
            const response = await api.getTemplate(theme, name);
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
            await api.putTemplate(selectedTheme.value, selectedTemplate.value, templateContent);
            setFetchedTemplateContent(templateContent);
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

        if (!confirm(__("reset_template_confirmation", name, theme))) {
            return;
        }

        try {
            setResetting(true);
            await api.deleteTemplate(theme, name);
            await loadTemplate(theme, name);
            infobox.showSuccess(__("template_reset"));
        } catch (e) {
            infobox.showError(e.toString());
        } finally {
            setResetting(false);
        }
    };

    const handleThemeChange = (selectedOption: SelectOption) => {
        if (areChangesUnsaved && !confirm(__("template_unsaved_changes_confirmation"))) {
            return;
        }

        setSelectedTheme(selectedOption);
    };

    const handleTemplateChange = (selectedOption: TemplateSelectOption) => {
        if (areChangesUnsaved && !confirm(__("template_unsaved_changes_confirmation"))) {
            return;
        }

        if (selectedOption === null) {
            setSelectedTemplate(undefined);
            setTemplateContent("");
            setFetchedTemplateContent("");
        } else {
            setSelectedTemplate(selectedOption);
        }
    };

    const handleTemplateContentChange = (editor, data, value) => setTemplateContent(value);

    useEffect(() => {
        loadThemeList().catch(handleError);
    }, []);

    // Reload templates on theme change
    useEffect(() => {
        loadTemplateList(selectedTheme.value).catch(handleError);
    }, [selectedTheme]);

    // Load template on theme/template change
    useEffect(() => {
        if (selectedTemplate) {
            loadTemplate(selectedTheme.value, selectedTemplate.value).catch(handleError);
        }
    }, [selectedTheme, selectedTemplate]);

    // Handle saving using ctrl + s
    useEffect(() => onKeyPress((e) => (e.ctrlKey || e.metaKey) && e.key == "s", updateTemplate), [
        templateContent,
    ]);

    // Set template if query is passed
    useEffect(() => {
        if (selectedTemplate) {
            return;
        }

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

    return (
        <>
            <div className="field is-grouped">
                <div className="control" style={{ minWidth: "150px" }}>
                    <Creatable
                        className="theme-selector"
                        options={themeList}
                        value={selectedTheme}
                        onChange={handleThemeChange}
                        isLoading={themeListLoading}
                    />
                </div>

                <div className="control is-expanded">
                    <Select
                        className="template-selector"
                        options={templateList}
                        value={selectedTemplate}
                        placeholder={__("select_template")}
                        onChange={handleTemplateChange}
                        isLoading={templateListLoading}
                        isClearable
                    />
                </div>

                <div className="control">
                    <button
                        className={classNames(["button is-success"], { "is-loading": updating })}
                        disabled={!areChangesUnsaved}
                        onClick={updateTemplate}
                    >
                        {__("save")}
                    </button>
                </div>
                <div className="control">
                    <button
                        className={classNames(["button is-danger"], { "is-loading": resetting })}
                        disabled={!selectedTemplate?.deletable}
                        onClick={resetTemplate}
                    >
                        {__("reset")}
                    </button>
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

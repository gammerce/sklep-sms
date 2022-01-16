import React, { FunctionComponent } from "react";
import { InputError } from "../types/general";

interface Props {
    errors: InputError;
}

export const FormError: FunctionComponent<Props> = (props) => {
    const { errors } = props;

    if (!errors) {
        return null;
    }

    const errorsList = Array.isArray(errors) ? errors : [errors];
    return (
        <ul className="help is-danger">
            {errorsList.map((error) => (
                <li key={error}>{error}</li>
            ))}
        </ul>
    );
};

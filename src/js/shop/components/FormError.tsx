import React, { FunctionComponent } from "react";
import { InputError } from "../types/general";

interface Props {
    errors: InputError;
}

export const FormError: FunctionComponent<Props> = (props) => {
    const errors = Array.isArray(props.errors) ? props.errors : [props.errors];
    return (
        <ul className="help is-danger">
            {errors.map((error) => (
                <li>{error}</li>
            ))}
        </ul>
    );
};

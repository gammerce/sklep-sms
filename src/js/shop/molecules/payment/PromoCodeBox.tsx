import React, {ChangeEvent, FunctionComponent, useState} from "react";
import {__} from "../../../general/i18n";

interface Props {
    hasPromoCode: boolean;
    onPromoCodeApply(promoCode: string): Promise<void>;
    onPromoCodeRemove(): Promise<void>;
}

export const PromoCodeBox: FunctionComponent<Props> = (props) => {
    const [promoCode, setPromoCode] = useState<string>("");
    const {hasPromoCode, onPromoCodeApply, onPromoCodeRemove} = props;

    const updatePromoCode = (e: ChangeEvent<HTMLInputElement>) => setPromoCode(e.target.value);
    const applyPromoCode = () => onPromoCodeApply(promoCode);
    const removePromoCode = async () => {
        await onPromoCodeRemove();
        setPromoCode("");
    }

    return (
        <div className="promo-code-box">
            <div className="field">
                <label className="label" htmlFor="promo_code">{__("promo_code")}</label>
                <div className="control">
                    <div className="field has-addons">
                        <div className="control">
                            <input
                                id="promo_code"
                                className="input"
                                placeholder={__("type_code")}
                                value={promoCode}
                                onChange={updatePromoCode}
                                disabled={hasPromoCode}
                            />
                        </div>
                        {
                            !hasPromoCode &&
                            <div className="control">
                                <button
                                    className="button is-primary"
                                    onClick={applyPromoCode}
                                    disabled={!promoCode}
                                >
                                        <span className="icon">
                                            <i className="fas fa-tag" />
                                        </span>
                                    <span>{__("use_code")}</span>
                                </button>
                            </div>
                        }
                        {
                            hasPromoCode &&
                            <div className="control">
                                <button className="button is-primary" onClick={removePromoCode}>
                                        <span className="icon">
                                            <i className="fas fa-trash" />
                                        </span>
                                    <span>{__("remove")}</span>
                                </button>
                            </div>
                        }

                    </div>
                </div>
            </div>
        </div>
    );
}
import { Button } from "@wordpress/components";
import copy from "copy-to-clipboard";
import React, { useCallback, useEffect, useState, useRef } from 'react';
import { withLabel, validFieldProps } from 'quickbuilder';
// import { validFieldProps } from '../core/utils';
const Input = (props, ref?) => {
	const type = props.type ? props.type : 'text';
	const validProps = validFieldProps({...props, type}, ['is_pro', 'visible', 'trigger', 'copyOnClick', 'disable', 'parentIndex', 'context', 'badge', 'popup', 'tags']);
	const handleChange = (event) => validProps.onChange(event, { popup: props?.popup, isPro: !!props.is_pro });
	const localRef = useRef(null);
	const inputRef = ref?.current ? ref : localRef;

	if (validProps.type === 'checkbox') {
		if (validProps?.name) {
			validProps.checked = validProps?.checked || validProps?.value;
		}
	}

	const [isCopied, setIsCopied] = useState(false);

	useEffect(() => {
		let  CopyInterval;
		if (isCopied) {
			CopyInterval = setTimeout(() => {
				setIsCopied(false);
			}, 2000);
		}
		return () => CopyInterval && clearTimeout(CopyInterval);
	}, [isCopied])



	if (!props.is_pro && props?.copyOnClick && props?.value) {
		const copyMessage = props?.copyMessage || "Click To Copy!";
		const copiedMessage = props?.copiedMessage || "Copied!";
		const handleCopy = () => {
			copy(props.value, {
				format: 'text/plain',
				onCopy: () => {
					setIsCopied(true);
				},
			});
		};

		return <span className="wprf-clipboard-wrapper">
			{React.createElement("input", { ...validProps, onChange: handleChange })}
			<span className="wprf-clipboard-tooltip">
				<span className="wprf-clipboard-tooltip-text">{isCopied ? copiedMessage : copyMessage}</span>
			<Button className="wprf-copy-icon" onClick={() => handleCopy()}>Copy</Button>
			</span>
		</span>;
	}

	return React.createElement('input', {
		...validProps, onChange: handleChange, ref: inputRef,
	})
}


export const GenericInput = React.memo(React.forwardRef(Input));
export default withLabel(React.memo(Input));
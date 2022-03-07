import { useEffect, useState } from "@wordpress/element";

const ToggleButton = ({ options, onChange, defaultSelected }) => {
	const [selected, setSelected] = useState(defaultSelected || options[0]);

	useEffect(() => {
		onChange(selected.value);
	}, [selected]);

	useEffect(() => {
		if (defaultSelected) {
			setSelected(defaultSelected);
		}
	}, [defaultSelected]);

	return (
		<div id="switch" className="eb-switch-control">
			{options.map((option) => (
				<label>
					<input
						type="radio"
						name="gh"
						placeholder="name"
						onChange={() => setSelected(option)}
					/>
					<span
						style={{
							color:
								selected.value === option.value
									? "white"
									: "black",
						}}>
						{option.label}
					</span>
				</label>
			))}
			<span
				className="slideBg"
				style={{
					backgroundColor: "#551ef7",
					transform:
						selected == options[0]
							? "translateX(0)"
							: "translateX(100%)",
				}}
			/>
		</div>
	);
};

export default ToggleButton;

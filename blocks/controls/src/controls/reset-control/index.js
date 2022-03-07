const ResetControl = ({  onReset, children }) => {
	return (
		<div className="eb-range-controller-container">
			{children}
			<button className="eb-range-reset-button" onClick={onReset}>
				<span className="dashicon dashicons dashicons-image-rotate"></span>
			</button>
		</div>
	);
};

export default ResetControl;

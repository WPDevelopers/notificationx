import { render } from "react-dom";
import Modal from "react-modal";
import ModalContainer from "./modal/ModalContainer";

window.onload = function() {
	// Inject export button div in toolbar
	let node = document.querySelector(".edit-post-header__settings");
	let newElem = document.createElement("div");
	let html = "<div id='eb-cloud-export'></div>";
	newElem.innerHTML = html;
	node.appendChild(newElem);

	// Bind modal with export button
	Modal.setAppElement("#eb-cloud-export");

	render(<ModalContainer />, document.getElementById("eb-cloud-export"));
};

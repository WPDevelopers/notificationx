import React, { Component } from "react";
import PropTypes from "prop-types";
import FontIconPicker from "@fonticonpicker/react-fonticonpicker";
import arrayMove from "array-move";
import SortableComponent from "./SortableComponent";
// import "./style.scss";

class SocialProfiles extends Component {
	state = {
		iconList: this.props.iconList,
		profiles: this.props.profiles || [], //example profiles: [{ icon: 'facebook', link: 'www.fb.com/john', isExpanded: false}]
		selectedIcon: null,
		url: "",
		color: "",
	};

	onSelectIcon = (selectedIcon) => {
		// When a social profile icon is selected, store it in state and pass it
		// to the callback function

		if (selectedIcon) {
			let icon = selectedIcon.replace(/^fab fa-/i, "");
			let profiles = [
				...this.state.profiles,
				{ icon: icon, color: "", link: "", isExpanded: false },
			];

			this.setState({ profiles, selectedIcon });
			this.props.onProfileAdd(profiles);
		}
	};

	onDeleteProfile = (position) => {
		// Remove clicked social profile, store rest of the
		// profiles in state, and pass deleted profile name to the callback function
		let profiles = [...this.state.profiles];
		profiles.splice(position, 1);

		this.setState({ profiles });
		this.props.onProfileAdd(profiles);
	};

	onProfileClick = (icon) => {
		// When a profile is clicked, expand/collapse link input form and
		// store profile icon name, url in state
		let profiles = [...this.state.profiles];
		let url = this.state.url;
		let selectedIcon = icon;
		let color = this.state.color;

		profiles = profiles.map((profile) => {
			if (profile.icon === icon) {
				url = profile.link;
				color = profile.color;
				return { ...profile, isExpanded: !profile.isExpanded };
			}

			return { ...profile, isExpanded: false };
		});

		this.setState({ profiles, selectedIcon, url, color });
	};

	onUrlChange = (event) => this.setState({ url: event.target.value });

	onSubmit = (icon) => {
		// When new link is submitted, store it in profile object, collapse input form and
		// pass updated profiles to callback function
		let profiles = [...this.state.profiles];
		let url = this.state.url;

		profiles = profiles.map((profile) =>
			profile.icon === icon
				? { ...profile, link: url, isExpanded: false }
				: profile
		);

		this.setState({ profiles, url: "" });
		this.props.onProfileAdd(profiles);
	};

	onSortEnd = ({ oldIndex, newIndex }) => {
		// Rearrange profiles array after drag and drop, pass
		// updated array to edit view
		let profiles = arrayMove(this.state.profiles, oldIndex, newIndex);

		this.setState({ profiles });
		this.props.onProfileAdd(profiles);
	};

	onColorChange = (color, index) => {
		let profiles = [...this.state.profiles];
		profiles[index].color = color;

		this.setState({ profiles });
		this.props.onProfileAdd(profiles);
	};

	render() {
		const { iconList, selectedIcon, url, profiles } = this.state;

		return (
			<div>
				<label>Social Media</label>
				<FontIconPicker
					icons={iconList}
					value={selectedIcon ? `fab fa-${selectedIcon}` : null}
					onChange={this.onSelectIcon}
					appendTo="body"
					iconsPerPage={25}
					closeOnSelect
				/>

				<SortableComponent
					profiles={profiles}
					onProfileClick={this.onProfileClick}
					onDeleteProfile={this.onDeleteProfile}
					selectedIcon={selectedIcon}
					url={url}
					onUrlChange={this.onUrlChange}
					onSubmit={this.onSubmit}
					onProfileAdd={this.onProfileAdd}
					onSortEnd={this.onSortEnd}
					onColorChange={this.onColorChange}
				/>
			</div>
		);
	}
}

SocialProfiles.propTypes = {
	iconList: PropTypes.array.isRequired,
	profiles: PropTypes.shape({
		icon: PropTypes.string.isRequired,
		color: PropTypes.string,
		link: PropTypes.string,
		isExpanded: PropTypes.bool.isRequired,
	}),
	onProfileAdd: PropTypes.func.isRequired,
};

export default SocialProfiles;

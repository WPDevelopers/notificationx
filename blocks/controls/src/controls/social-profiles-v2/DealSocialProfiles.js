import { useState, useEffect } from "@wordpress/element";

import FontIconPicker from "@fonticonpicker/react-fonticonpicker";
import arrayMove from "array-move";
import SortableComponent from "./SortableComponent";

export default function SocialProfiles({
	iconList,
	onProfileAdd,
	profiles: propProfiles,
}) {
	//   const [iconList, setIconList] = useState(propIconList);
	const [profiles, setProfiles] = useState(propProfiles || []); //example profiles: [{ icon: 'facebook', link: 'www.fb.com/john', isExpanded: false}]
	const [selectedIcon, setSelectedIcon] = useState(null);
	const [url, setUrl] = useState("");
	const [color, setColor] = useState("");

	const onSelectIcon = (selectedIcon) => {
		// When a social profile icon is selected, store it in state and pass it
		// to the callback function

		if (selectedIcon) {
			let newProfiles = [
				...profiles,
				{
					icon: selectedIcon,
					// color: "#fff",
					// bgColor: "#000",
					link: "#",
					isExpanded: false,
				},
			];

			setProfiles(newProfiles);
			setSelectedIcon(selectedIcon);

			onProfileAdd(newProfiles);
		}
	};

	const onDeleteProfile = (position) => {
		// Remove clicked social profile, store rest of the
		// profiles in state, and pass deleted profile name to the callback function
		let newProfiles = [...profiles];
		newProfiles.splice(position, 1);

		setProfiles(newProfiles);
		onProfileAdd(newProfiles);
	};

	const onProfileClick = (icon) => {
		// When a profile is clicked, expand/collapse link input form and
		// store profile icon name, url in state
		let newProfiles = [...profiles];
		let newUrl = url;
		let newColor = color;

		newProfiles = newProfiles.map((profile) => {
			if (profile.icon === icon) {
				newUrl = profile.link;
				newColor = profile.color;
				return { ...profile, isExpanded: !profile.isExpanded };
			}

			return { ...profile, isExpanded: false };
		});

		setProfiles(newProfiles);
		setSelectedIcon(icon);
		setUrl(newUrl);
		setColor(newColor);
	};

	const onUrlChange = (e) => {
		setUrl(e.target.value);
	};

	useEffect(() => {
		if (!url) return;
		let newProfiles = profiles.map((profile) =>
			profile.icon === selectedIcon
				? {
						...profile,
						link: url || profile.link,
						// isExpanded: false
				  }
				: profile
		);
		setProfiles(newProfiles);
		onProfileAdd(newProfiles);
	}, [url]);

	// const onSubmit = (icon) => {
	//   // When new link is submitted, store it in profile object, collapse input form and pass updated profiles to callback function

	//   let newProfiles = [...profiles].map((profile) =>
	//     profile.icon === icon
	//       ? {
	//           ...profile,
	//           link: url,
	//           // isExpanded: false
	//         }
	//       : profile
	//   );

	//   setProfiles(newProfiles);
	//   onProfileAdd(newProfiles);
	// };

	const onSortEnd = ({ oldIndex, newIndex }) => {
		// Rearrange profiles array after drag and drop, pass
		// updated array to edit view
		const newProfiles = arrayMove(profiles, oldIndex, newIndex);

		setProfiles(newProfiles);
		onProfileAdd(newProfiles);
	};

	const onColorChange = (color, index) => {
		let newProfiles = [...profiles];
		newProfiles[index].color = color;

		setProfiles(newProfiles);
		onProfileAdd(newProfiles);
	};

	const onBgColorChange = (bgColor, index) => {
		let newProfiles = [...profiles];
		newProfiles[index].bgColor = bgColor;

		setProfiles(newProfiles);
		onProfileAdd(newProfiles);
	};

	return (
		<div>
			<style>{`

      li.drag-helper .iconLbl{
        color: #5f5f5f;
        padding-bottom: 5px;
        display: block;
      }

      li.drag-helper .input_wrapp{
        display: flex;
        align-items:center;
      }

      li.drag-helper .save-button{
        margin:0;
        padding: 4px;
        cursor:pointer;
      }

      li.drag-helper .social-link-input{
        margin: 0;
        flex: 1;
        padding: 0px 5px;
      }

      .socialBarsLabel{
        display:block;
        padding: 15px 0 5px;
        cursor:default;
      }


      `}</style>

			<label>Social Media</label>
			<FontIconPicker
				icons={iconList}
				value={selectedIcon || null}
				onChange={onSelectIcon}
				appendTo="body"
				iconsPerPage={20}
				closeOnSelect
			/>

			{profiles.length > 0 && (
				<label className="socialBarsLabel">
					<i>Click on the social bars below to expand more options</i>
				</label>
			)}

			<SortableComponent
				profiles={profiles}
				onProfileClick={onProfileClick}
				onDeleteProfile={onDeleteProfile}
				selectedIcon={selectedIcon}
				url={url}
				onUrlChange={onUrlChange}
				// onSubmit={onSubmit}
				onProfileAdd={onProfileAdd}
				onSortEnd={onSortEnd}
				onColorChange={onColorChange}
				onBgColorChange={onBgColorChange}
			/>
		</div>
	);
}

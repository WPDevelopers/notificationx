// Import the favloader and interval modules
import FavLoader from "./flashing/favloader";
import interval from "./flashing/webWorker";

// Declare constants for the settings and messages
// @ts-ignore
const settings = window.nx_flashing_tab || {};
const initialDelay = (parseInt(settings.ft_delay_before) || 0) * 1000;
const delayBetween = (parseInt(settings.ft_delay_between) || 1) * 1000;
const displayFor = (parseFloat(settings.ft_display_for) || 0) * 1000 * 60;
let message1 = { message: "", icon: "" };
let message2 = { message: "", icon: "" };
const nx_id = settings.nx_id;
const restUrl = settings.__rest_api_url;

switch (settings.themes) {
    case "flashing_tab_theme-1":
    case "flashing_tab_theme-2":
        message1.icon = settings.ft_theme_one_icons?.["icon-one"];
        message2.icon = settings.ft_theme_one_icons?.["icon-two"];
        message1.message = settings.ft_theme_one_message;
        break;
    case "flashing_tab_theme-3":
        message1 = settings.ft_theme_three_line_one ?? message1;
        message2 = settings.ft_theme_three_line_two ?? message2;
        break;
    case "flashing_tab_theme-4":
        message1 = settings.ft_theme_three_line_one ?? message1;
        if (!settings.ft_theme_four_line_two?.["is-show-empty"]) {
            message2 = settings.ft_theme_four_line_two?.default ?? message2;
        } else {
            message2 = settings.ft_theme_four_line_two?.alternative ?? message2;
        }
        break;

    default:
        break;
}

// Initialize the favloader with the given parameters
FavLoader.init({
    size: 32,
});

// Declare variables for the toggle and interval states
let toggle = false;
let intervalId = null;
let initialDelayID = null;
let displayForID = null;

// Save the original title of the document
const originalTitle = window.document.title;

// Define a function to change the title based on the toggle state and the message
const changeTitle = (message) => {
    if (message && message !== document.title) {
        document.title = message;
    }
};

// Define a function to animate the icon based on the icon url
const animateIcon = (icon) => {
    if (icon) {
        FavLoader.animatePng(icon);
    }
};

// Define a function to switch between the messages and icons based on the toggle state
const switchMessageAndIcon = () => {
    toggle = !toggle;
    if (toggle) {
        // Use message1
        changeTitle(message1.message);
        animateIcon(message1.icon);
    } else {
        // Use message2
        changeTitle(message2.message);
        animateIcon(message2.icon);
    }
};

// Define a function to clear the title and icon and stop the intervals
const clear = () => {
    console.trace();
    document.title = originalTitle;
    FavLoader.stop();
    if (intervalId) {
        interval.clear(intervalId);
        intervalId = null;
    }
    if (initialDelayID) {
        interval.clearTimeout(initialDelayID);
        initialDelayID = null;
    }
    if (displayForID) {
        interval.clearTimeout(displayForID);
        displayForID = null;
    }
};

// Define a function that takes nx_id and type as parameters
function sendAnalyticsRequest(nx_id, type) {
    // Create an object with the request parameters
    let params = {
        nx_id: nx_id,
        type: type,
    };

    // Use the fetch api to send a POST request
    fetch(restUrl, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(params),
    })
        .then((response) => response.json()) // Parse the response as json
        .then((data) => {
            // Do something with the data
            // console.log(data);
        })
        .catch((error) => {
            // Handle any errors
            // console.error(error);
        });
}

// Add an event listener for the visibility change of the document
window.addEventListener("visibilitychange", (event) => {
    if (document.visibilityState !== "visible") {
        clear();
        // Set a timeout to start switching between the messages and icons after a delay
        initialDelayID = interval.setTimeout(() => {
            intervalId = interval.set(switchMessageAndIcon, delayBetween);

            // reset initialDelayID so we can detect it in clicks analytics
            initialDelayID = null;
            sendAnalyticsRequest(nx_id, "views");
        }, initialDelay);

        if (displayFor) {
            displayForID = interval.setTimeout(() => {
                clear();
            }, displayFor);
        }
    } else {
        // making sure we are sending request after initialDelay.
        if (null == initialDelayID) {
            sendAnalyticsRequest(nx_id, "clicks");
        }
        clear();
    }
});

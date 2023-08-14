// Import the favloader and interval modules
import FavLoader from "./flashing/favloader";
import interval from "./flashing/webWorker";

// Declare constants for the settings and messages
// @ts-ignore
const settings     = window.nx_flashing_tab || {};
const initialDelay = (parseInt(settings.ft_delay_before) || 0) * 1000;
const delayBetween = (parseInt(settings.ft_delay_between) || 1) * 1000;
const displayFor   = (parseFloat(settings.ft_display_for) || 0) * 1000 * 60;
let   message1     = { message: "", icon: "" };
let   message2     = { message: "", icon: "" };
const nx_id        = settings.nx_id;
const restUrl      = settings.__rest_api_url;

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
let toggle = 0;
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
    return FavLoader.animatePng(icon);
};

// Define a function to switch between the messages and icons based on the toggle state
const enableOT     = settings.ft_enable_original_icon_title || false;
const switchMessageAndIcon = () => {
    // if enableOT then will run the else part otherwise toggle first two messages
    const modulus = enableOT ? 3 : 2;
    console.log("toggle", toggle);

    if (toggle === 0) {
        // Use message1
        animateIcon(message1.icon).finally(() => {
            changeTitle(message1.message);
        });
    } else if (toggle === 1) {
        // Use message2
        animateIcon(message2.icon).finally(() => {
            changeTitle(message2.message);
        });
    } else if(toggle === 2) {
        // Use message3
        FavLoader.restore();
        changeTitle(originalTitle);
    }
    toggle = (toggle + 1) % modulus;
};

// Define a function to clear the title and icon and stop the intervals
const clear = (removeIcon?) => {
    // console.trace();
    document.title = originalTitle;

    if(removeIcon){
        FavLoader.removeIcon();
    }
    else{
        FavLoader.restore();
    }

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
        clear(true);
        // Set a timeout to start switching between the messages and icons after a delay
        initialDelayID = interval.setTimeout(() => {
            switchMessageAndIcon();
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

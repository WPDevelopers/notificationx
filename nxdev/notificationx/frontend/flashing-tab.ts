// Import the favloader and interval modules
import favloader from "./flashing/favloader";
import interval from "./flashing/webWorker";

// Declare constants for the settings and messages
// @ts-ignore
const settings     = window.nx_flashing_tab || {};
const initialDelay = (parseInt(settings.ft_delay_before) || 0) * 1000;
const delayBetween = (parseInt(settings.ft_delay_between) || 1) * 1000;
const displayFor   = (parseInt(settings.ft_display_for) || 0) * 1000 * 60;
const message1     = settings.ft_message_1;
const message2     = settings.ft_message_2;

// Initialize the favloader with the given parameters
favloader.init({
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
    if (message) {
        document.title = message;
    }
};

// Define a function to animate the icon based on the icon url
const animateIcon = (icon) => {
    if (icon) {
        favloader.animatePng(icon);
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
    document.title = originalTitle;
    favloader.stop();
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

// Add an event listener for the visibility change of the document
window.addEventListener("visibilitychange", (event) => {
    clear();
    if (document.visibilityState !== "visible") {
        // Set a timeout to start switching between the messages and icons after a delay
        initialDelayID = interval.setTimeout(() => {
            intervalId = interval.set(switchMessageAndIcon, delayBetween);
        }, initialDelay);

        if (displayFor) {
            displayForID = interval.setTimeout(() => {
                clear();
            }, displayFor);
        }
    }
});

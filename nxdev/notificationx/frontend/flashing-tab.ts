// Import the favloader and interval modules
import favloader from "./flashing/favloader";
import interval from "./flashing/webWorker";

// Declare constants for the settings and messages
// @ts-ignore
const settings = window.nx_flashing_tab;
const initialDelay = 100;
const delayBetween = 1000;
const message1 = {
 icon : 'https://nxm.test/wp-content/plugins/notificationx/assets/public/image/flashing-tab/theme-3-icon-1.png',
 message: 'Comeback!',
}
const message2 = {
 icon : 'https://nxm.test/wp-content/plugins/notificationx/assets/public/image/flashing-tab/theme-3-icon-2.png',
 message: null,
 // message: 'You forgot to purchase!',
}

// Check if the icon is a gif
const isGif = (message1?.icon?.match(/\.gif$/) != null);

// Initialize the favloader with the given parameters
favloader.init({
 size: 16,
 radius: 6,
 thickness: 2,
 color: '#0F60A8',
 gif: isGif ? message1.icon : null,
});

// Declare variables for the toggle and interval states
let toggle = false;
let intervalId = null;
let timeoutId = null;

// Save the original title of the document
const originalTitle = window.document.title;

// Define a function to change the title based on the toggle state and the message
const changeTitle = (message) => {
 if(message) {
   document.title = message;
 }
}

// Define a function to animate the icon based on the toggle state and the icon url
const animateIcon = (icon) => {
 if(!isGif && icon) {
   favloader.animatePng(icon);
 }
}

// Define a function to switch between the messages and icons based on the toggle state
const switchMessageAndIcon = () => {
 toggle = !toggle;
 if(toggle){
   // Use message1
   changeTitle(message1.message);
   animateIcon(message1.icon);
 }
 else{
   // Use message2
   changeTitle(message2.message);
   animateIcon(message2.icon);
 }
}

// Define a function to clear the title and icon and stop the intervals
const clear = () => {
 document.title = originalTitle;
 favloader.stop();
 if (intervalId) {
   interval.clear(intervalId);
   intervalId = null;
 }
 if (timeoutId) {
   clearTimeout(timeoutId);
   timeoutId = null;
 }
}

// Add an event listener for the visibility change of the document
window.addEventListener('visibilitychange', (event) => {
 clear();
 if (document.visibilityState !== "visible") {
   // Start the favloader if it is a gif
   if(isGif){
     favloader.start();
   }

   // Set a timeout to start switching between the messages and icons after a delay
   timeoutId = setTimeout(() => {
     intervalId = interval.set(switchMessageAndIcon, delayBetween);
   }, initialDelay);

 }
});

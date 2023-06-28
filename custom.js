const hotSpotReplace = () => {
	const textOf = document.querySelector(".h5p-question-feedback-content-text");
	textOf.innerText = textOf.innerText.replace(" of ", " von ");
}

const hotSpotFix = () => {
	const wrapper = document.querySelector(".image-hotspot-question .image-wrapper");
	if (!wrapper) { return;}
	const observeWrapper = new MutationObserver( mutations => {
		mutations.forEach(mutation => {
			if (mutation.type === "childList") {
				hotSpotReplace();
			}
		})
	})
	observeWrapper.observe(wrapper, { childList: true });
}

const openFullScreen = elem => {
	if (elem.requestFullscreen) {
		elem.requestFullscreen();
	} else if (elem.webkitRequestFullscreen) {
		elem.webkitRequestFullscreen();
	} else if (elem.msRequestFullscreen) {
		elem.msRequestFullscreen();
	}
}

const FullScreenToggler = () => {
	const currentEnv = document.body.classList;
	const h5pContainer = document.querySelector(".h5p-container.h5p-standalone");
	const hasControls = Boolean(h5pContainer.querySelector(".h5p-content-controls"));
	if (!hasControls) {
		//build the toggler like the h5p integrated one to look same
		const cControls = document.createElement("div");
		cControls.classList.add("h5p-content-controls");
		const btnFullscreen = document.createElement("div");
		btnFullscreen.classList.add("h5p-enable-fullscreen");
		btnFullscreen.setAttribute("role", "button");
		btnFullscreen.setAttribute("tabindex", "0");
		btnFullscreen.setAttribute("aria-label", "Fullscreen");
		btnFullscreen.setAttribute("title", "Fullscreen");
		cControls.appendChild(btnFullscreen);
		h5pContainer.appendChild(cControls);
		btnFullscreen.onclick = e => {
			e.preventDefault();
			e.stopPropagation();
			openFullScreen(document.body);
		}
	}
}

window.addEventListener("load", hotSpotFix);
window.addEventListener("load", FullScreenToggler);

const referrerToClass = ref => {
	const refClassMapping = {
		"vorschau.test-dilewe.de": "dbhessen",
		"demokratie-bildung-hessen.de": "dbhessen",
		"archiv-buergerbewegung-leipzigx.test-dilewe.de": "dbhessen",

		"redaktionsvorschau.lasub.dilewe.de": "lasub",
		"module-sachsen.dilewe.de": "lasub",
		"lasub.staging.test-dilewe.de": "lasub",

		"vorschau-netbook.dilewe.de": "netbook",
		"h5p-netbook.test-dilewe.de": "netbook",

	};
	return refClassMapping[ref] || "unknown";
};

document.addEventListener("DOMContentLoaded", () => {
	document.body.classList.add(referrerToClass(String(document.referrer).split("/")[2]));
}, false);

H5P.externalDispatcher.on('xAPI', function (event) {
	const data = JSON.stringify(event, null, 2);
	const parentUrl = (window.location !== window.parent.location)
		? document.referrer
		: document.location.href;
	if(parentUrl !== "") {
		window.parent.postMessage(data, parentUrl);
	} else {
		console.warn("Empty parentUrl: ", parentUrl);
	}
});

const replaceWithGermanQuotes = (node) => {
	node.childNodes.forEach(replaceWithGermanQuotes);
	if(node.nodeType === 3){ // Text node
		let oldText = node.textContent;
		const newText = oldText
			.replaceAll("„",'"')
			.replaceAll('”', '"')
			.replaceAll('“', '"')
			.replace(/"(.*?)"/g, '„$1“');
		if(newText !== oldText){
			node.textContent = newText;
		}
	}
};

const replaceWithGermanQuotesHandler = (event) => {
	const $target = event.data.$target;
	if(!$target){return;}
	for(let i=0;i<$target.length;i++){
		replaceWithGermanQuotes($target[i]);
	}
};

H5P.externalDispatcher.on('domChanged', replaceWithGermanQuotesHandler);
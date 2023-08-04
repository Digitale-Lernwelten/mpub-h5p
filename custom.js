(() => {
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
	H5P.externalDispatcher.on('domChanged', FullScreenToggler);
	H5P.externalDispatcher.on('domChanged', hotSpotFix);

	const getUserDataTimeouts = new Map();
	const getUserDataPromiseResolver = new Map();
	window.addEventListener('message', (event) => {
		const msg = event.data || {};
		switch(msg.T){
		case "GetUserData":
			const id = msg.id;
			if(!id){
				throw new Error("Invalid message, GetUserData without id");
			}

			const data = msg.data;
			if(data){
				for(const res of getUserDataPromiseResolver.get(id) || []){
					setTimeout(() => res(data), 0);
				}
				getUserDataPromiseResolver.delete(id);
			}
			break;
		default:
			break;
		}
	});
	
	const cpGetUserData = (contentId, subContentId, dataId) => {
		const id = `${contentId}-${subContentId}-${dataId}`;
		const prom = new Promise((res) => {
			if((getUserDataPromiseResolver.get(id) || []).length <= 0){
				const msg = {
					T: "GetUserData",
					id
				};
				parent.postMessage(msg, "*");
				getUserDataTimeouts.set(id, setTimeout(() => {
					for(const res of getUserDataPromiseResolver.get(id) || []){
						setTimeout(() => res(), 0);
					}
					getUserDataPromiseResolver.delete(id);
					getUserDataTimeouts.delete(id);
				}, 100));
			}
			if(!getUserDataPromiseResolver.get(id)){
				getUserDataPromiseResolver.set(id, []);
			}
			getUserDataPromiseResolver.get(id).push(res);
		});
		return prom;
	};

	const cpSaveUserData = async (contentId, subContentId, dataId, data) => {
		const id = `${contentId}-${subContentId}-${dataId}`;
		const msg = {
			T: "SaveUserData",
			id,
			data,
		};
		parent.postMessage(msg, "*");
	};

	H5P.getUserData = function (contentId, dataId, done, subContentId) {
		if (!subContentId) {
			subContentId = 0;
		}

		cpGetUserData(contentId, subContentId, dataId).then((data) => {
			done(undefined, data);
		}).catch(err => {
			done(err);
		});
	};
	
	H5P.setUserData = function (contentId, dataId, data, extras) {
		cpSaveUserData(contentId, 0, dataId, data);
	};
})();
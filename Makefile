install-cms:
	cd home/cms && npm install
	cd -

install-homesite:
	cd home/site && npm install
	cd -

run-cms:
	cd home/cms && npm run dev
	cd -

run-homesite:
	cd home/site && npm run dev
	cd -
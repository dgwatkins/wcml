#!/bin/bash

STAGED_FILES=`git diff --cached --name-only --diff-filter=ACMR HEAD | grep -E '^src/(yarn.lock|package.json)'`

for FILE in ${STAGED_FILES}
do
	FILES="$FILES ./$FILE"
done

if [[ "$FILES" != "" ]]
then
	echo "Validating package.json"
    yarn check

    if [[ $? != 0 ]]
    then
        exit 1
    fi
fi

exit $?

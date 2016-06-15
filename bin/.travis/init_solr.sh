#!/usr/bin/env bash

default_config_files[0]='lib/Resources/config/solr/schema.xml'
default_config_files[1]='lib/Resources/config/solr/custom-fields-types.xml'
default_config_files[2]='lib/Resources/config/solr/language-fieldtypes.xml'

default_cores[0]='core0'
default_cores[1]='core1'
default_cores[2]='core2'
default_cores[3]='core3'

SOLR_PORT=${SOLR_PORT:-8983}
SOLR_VERSION=${SOLR_VERSION:-4.10.4}
SOLR_CORES=${SOLR_CORES:-${default_cores[*]}}
SOLR_CONFIG=${SOLR_CONFIG:-${default_config_files[*]}}
SOLR_BACKGROUND=${SOLR_BACKGROUND:-false}
SOLR_DEBUG=${SOLR_DEBUG:-false}
SOLR_DIR=${SOLR_DIR:-__solr}

check() {
    if [ ! -d ${SOLR_DIR} ] ; then
        echo 'Installation directory does not exists'
        mkdir ${SOLR_DIR}
        echo "Created installation directory ${SOLR_DIR}"
    fi
}

download() {
    case ${SOLR_VERSION} in
        4.10.3)
            url="http://archive.apache.org/dist/lucene/solr/4.10.3/solr-4.10.3.tgz"
            dir_conf="collection1/conf/"
            ;;
        4.10.4)
            url="http://archive.apache.org/dist/lucene/solr/4.10.4/solr-4.10.4.tgz"
            dir_conf="collection1/conf/"
            ;;
        *)
            echo "Sorry, ${SOLR_VERSION} is not supported or not valid version"
            exit 1
            ;;
    esac

    installation_dir="${SOLR_DIR}/${SOLR_VERSION}"
    archive_file_name="${SOLR_VERSION}.tgz"
    installation_archive_file="${SOLR_DIR}/${archive_file_name}"

    if [ ! -d ${installation_dir} ] ; then
        echo "Installation ${SOLR_VERSION} does not exists"

        if [ ! -f ${installation_archive_file} ] ; then
            echo "Installation archive ${archive_file_name} does not exist"
            echo "Downloading Solr from ${url}..."
            curl -o ${installation_archive_file} ${url}
            echo 'Downloaded'
        fi

        echo "Extracting from installation archive ${archive_file_name}..."
        mkdir ${installation_dir}
        tar -zxf ${installation_archive_file} -C ${installation_dir} --strip-components=1
        echo 'Extracted'
    else
        echo "Found existing ${SOLR_VERSION} installation"
    fi
}

configure() {
    dir_name=${SOLR_DIR}/${SOLR_VERSION}

    # remove default cores configuration
    sed -i.bak 's/<core name=".*" instanceDir=".*" \/>//g' ${dir_name}/example/multicore/solr.xml
    for solr_core in ${SOLR_CORES[@]} ; do
        add_core ${solr_core}
    done
}

add_core() {
    solr_core=$1
    dir_name=${SOLR_DIR}/${SOLR_VERSION}
    core_dir="${dir_name}/example/multicore/${solr_core}"
    conf_source_dir="${dir_name}/example/solr/collection1/conf"

    # add core configuration
    sed -i.bak "s/<shardHandlerFactory/<core name=\"$solr_core\" instanceDir=\"$solr_core\" \/><shardHandlerFactory/g" ${dir_name}/example/multicore/solr.xml

    # prepare core directories
    [[ -d "${core_dir}" ]] || mkdir ${core_dir}
    [[ -d "${core_dir}/conf" ]] || mkdir ${core_dir}/conf

    # copy currency.xml, stopwords.txt and synonyms.txt
    cp ${conf_source_dir}/currency.xml ${core_dir}/conf/
    cp ${conf_source_dir}/stopwords.txt ${core_dir}/conf/
    cp ${conf_source_dir}/synonyms.txt ${core_dir}/conf/

    # copy core0 solrconfig.xml and patch it for current core
    if [ ! -f ${core_dir}/conf/solrconfig.xml ] ; then
        cp ${dir_name}/example/multicore/core0/conf/solrconfig.xml ${core_dir}/conf/
        sed -i.bak s/core0/"${solr_core}"/g ${core_dir}/conf/solrconfig.xml
    fi

    copy_configuration "${core_dir}/conf"

    echo "Configured core ${solr_core}"
}

copy_configuration() {
    destination_dir_name=$1

    if [ -d "${SOLR_CONFIG}" ] ; then
      cp -R ${SOLR_CONFIG}/* ${destination_dir_name}
    else
      for file in ${SOLR_CONFIG} ; do
        if [ -f "${file}" ] ; then
            cp ${file} ${destination_dir_name}
        else
            echo "${file} is not valid"
            exit 1
        fi
      done
    fi
}

run() {
    # Run solr
    echo "Running with version ${SOLR_VERSION}"
    echo "Starting solr on port ${SOLR_PORT}..."

    # go to the solr folder
    cd ${SOLR_DIR}/${SOLR_VERSION}/example

    if [ "$SOLR_BACKGROUND" = false ] ; then
        java -Djetty.port=${SOLR_PORT} -Dsolr.solr.home=multicore -jar start.jar
    else
        if [ "$SOLR_DEBUG" = "true" ] ; then
            java -Djetty.port=${SOLR_PORT} -Dsolr.solr.home=multicore -jar start.jar &
        else
            java -Djetty.port=${SOLR_PORT} -Dsolr.solr.home=multicore -jar start.jar > /dev/null 2>&1 &
        fi

        wait_for_solr

        cd ../../../
        echo 'Started'
    fi
}

wait_for_solr() {
    while ! is_solr_up ; do
        sleep 3
    done
}

is_solr_up() {
    address="http://localhost:${SOLR_PORT}/solr/admin/cores"
    echo "Checking if Solr is up on ${address}"
    http_code=`echo $(curl -s -o /dev/null -w "%{http_code}" ${address})`
    return `test ${http_code} = "200"`
}

check
download
configure
run

platform () {
    platform='unknown'
    unamestr=`uname`
    if [[ "$unamestr" == 'Linux' ]]; then
       platform='linux'
    elif [[ "$unamestr" == 'FreeBSD' ]]; then
       platform='freebsd'
    elif [[ "$unamestr" == 'Darwin' ]]; then
       platform='osx'
    fi
    echo $platform
}
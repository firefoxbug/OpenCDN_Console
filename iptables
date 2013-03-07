#!/bin/sh
#
# iptables        Start iptables firewall
#
# chkconfig: 2345 08 92
# description:        Starts, stops and saves iptables firewall
#
# config: /etc/sysconfig/iptables
# config: /etc/sysconfig/iptables-config

# Source function library.
. /etc/init.d/functions

IPTABLES=iptables
IPTABLES_DATA=/etc/sysconfig/$IPTABLES
IPTABLES_CONFIG=/etc/sysconfig/${IPTABLES}-config
IPV=${IPTABLES%tables} # ip for ipv4 | ip6 for ipv6
PROC_IPTABLES_NAMES=/proc/net/${IPV}_tables_names
VAR_SUBSYS_IPTABLES=/var/lock/subsys/$IPTABLES

if [ ! -x /sbin/$IPTABLES ]; then
    echo -n $"/sbin/$IPTABLES does not exist."; warning; echo
    exit 0
fi

if lsmod 2>/dev/null | grep -q ipchains ; then
    echo -n $"ipchains and $IPTABLES can not be used together."; warning; echo
    exit 1
fi

# Old or new modutils
/sbin/modprobe --version 2>&1 | grep -q module-init-tools \
    && NEW_MODUTILS=1 \
    || NEW_MODUTILS=0

# Default firewall configuration:
IPTABLES_MODULES=""
IPTABLES_MODULES_UNLOAD="yes"
IPTABLES_SAVE_ON_STOP="no"
IPTABLES_SAVE_ON_RESTART="no"
IPTABLES_SAVE_COUNTER="no"
IPTABLES_STATUS_NUMERIC="yes"

# Load firewall configuration.
[ -f "$IPTABLES_CONFIG" ] && . "$IPTABLES_CONFIG"

rmmod_r() {
    # Unload module with all referring modules.
    # At first all referring modules will be unloaded, then the module itself.
    local mod=$1
    local ret=0
    local ref=

    # Get referring modules.
    # New modutils have another output format.
    [ $NEW_MODUTILS = 1 ] \
        && ref=`lsmod | awk "/^${mod}/ { print \\\$4; }" | tr ',' ' '` \
        || ref=`lsmod | grep ^${mod} | cut -d "[" -s -f 2 | cut -d "]" -s -f 1`

    # recursive call for all referring modules
    for i in $ref; do
        rmmod_r $i
        let ret+=$?;
    done

    # Unload module.
    # The extra test is for 2.6: The module might have autocleaned,
    # after all referring modules are unloaded.
    if grep -q "^${mod}" /proc/modules ; then
        modprobe -r $mod > /dev/null 2>&1
        let ret+=$?;
    fi

    return $ret
}

flush_n_delete() {
    # Flush firewall rules and delete chains.
    [ -e "$PROC_IPTABLES_NAMES" ] || return 1

    # Check if firewall is configured (has tables)
    tables=`cat $PROC_IPTABLES_NAMES 2>/dev/null`
    [ -z "$tables" ] && return 1

    echo -n $"Flushing firewall rules: "
    ret=0
    # For all tables
    for i in $tables; do
        # Flush firewall rules.
        $IPTABLES -t $i -F;
        let ret+=$?;

        # Delete firewall chains.
        $IPTABLES -t $i -X;
        let ret+=$?;

        # Set counter to zero.
        $IPTABLES -t $i -Z;
        let ret+=$?;
    done

    [ $ret -eq 0 ] && success || failure
    echo
    return $ret
}

set_policy() {
    # Set policy for configured tables.
    policy=$1

    # Check if iptable module is loaded
    [ ! -e "$PROC_IPTABLES_NAMES" ] && return 1

    # Check if firewall is configured (has tables)
    tables=`cat $PROC_IPTABLES_NAMES 2>/dev/null`
    [ -z "$tables" ] && return 1

    echo -n $"Setting chains to policy $policy: "
    ret=0
    for i in $tables; do
        echo -n "$i "
        case "$i" in
           security)
                    $IPTABLES -t security -P INPUT $policy \
                    && $IPTABLES -t security -P OUTPUT $policy \
                    && $IPTABLES -t security -P FORWARD $policy \
                   || let ret+=1
                ;;
            raw)
                $IPTABLES -t raw -P PREROUTING $policy \
                    && $IPTABLES -t raw -P OUTPUT $policy \
                    || let ret+=1
                ;;
            filter)
                $IPTABLES -t filter -P INPUT $policy \
                    && $IPTABLES -t filter -P OUTPUT $policy \
                    && $IPTABLES -t filter -P FORWARD $policy \
                    || let ret+=1
                ;;
            nat)
                $IPTABLES -t nat -P PREROUTING $policy \
                    && $IPTABLES -t nat -P POSTROUTING $policy \
                    && $IPTABLES -t nat -P OUTPUT $policy \
                    || let ret+=1
                ;;
            mangle)
                $IPTABLES -t mangle -P PREROUTING $policy \
                    && $IPTABLES -t mangle -P POSTROUTING $policy \
                    && $IPTABLES -t mangle -P INPUT $policy \
                    && $IPTABLES -t mangle -P OUTPUT $policy \
                    && $IPTABLES -t mangle -P FORWARD $policy \
                    || let ret+=1
                ;;
            *)
                let ret+=1
                ;;
        esac
    done

    [ $ret -eq 0 ] && success || failure
    echo
    return $ret
}

start() {
    # Do not start if there is no config file.
    [ -f "$IPTABLES_DATA" ] || return 1

    echo -n $"Applying $IPTABLES firewall rules: "

    OPT=
    [ "x$IPTABLES_SAVE_COUNTER" = "xyes" ] && OPT="-c"

    $IPTABLES-restore $OPT $IPTABLES_DATA
    if [ $? -eq 0 ]; then
        success; echo
    else
        failure; echo; return 1
    fi
    
    # Load additional modules (helpers)
    if [ -n "$IPTABLES_MODULES" ]; then
        echo -n $"Loading additional $IPTABLES modules: "
        ret=0
        for mod in $IPTABLES_MODULES; do
            echo -n "$mod "
            modprobe $mod > /dev/null 2>&1
            let ret+=$?;
        done
        [ $ret -eq 0 ] && success || failure
        echo
    fi
    
    touch $VAR_SUBSYS_IPTABLES
    return $ret
}

stop() {
    # Do not stop if iptables module is not loaded.
    [ -e "$PROC_IPTABLES_NAMES" ] || return 1

    flush_n_delete
    set_policy ACCEPT
    
    if [ "x$IPTABLES_MODULES_UNLOAD" = "xyes" ]; then
        echo -n $"Unloading $IPTABLES modules: "
        ret=0
        rmmod_r ${IPV}_tables
        let ret+=$?;
        rmmod_r ${IPV}_conntrack
        let ret+=$?;
        [ $ret -eq 0 ] && success || failure
        echo
    fi
    
    rm -f $VAR_SUBSYS_IPTABLES
    return $ret
}

save() {
    # Check if iptable module is loaded
    [ ! -e "$PROC_IPTABLES_NAMES" ] && return 1

    # Check if firewall is configured (has tables)
    tables=`cat $PROC_IPTABLES_NAMES 2>/dev/null`
    [ -z "$tables" ] && return 1

    echo -n $"Saving firewall rules to $IPTABLES_DATA: "

    OPT=
    [ "x$IPTABLES_SAVE_COUNTER" = "xyes" ] && OPT="-c"

    ret=0
    TMP_FILE=`/bin/mktemp -q /tmp/$IPTABLES.XXXXXX` \
        && chmod 600 "$TMP_FILE" \
        && $IPTABLES-save $OPT > $TMP_FILE 2>/dev/null \
        && size=`stat -c '%s' $TMP_FILE` && [ $size -gt 0 ] \
        || ret=1
    if [ $ret -eq 0 ]; then
        if [ -e $IPTABLES_DATA ]; then
            cp -f $IPTABLES_DATA $IPTABLES_DATA.save \
                && chmod 600 $IPTABLES_DATA.save \
                || ret=1
        fi
        if [ $ret -eq 0 ]; then
            cp -f $TMP_FILE $IPTABLES_DATA \
                && chmod 600 $IPTABLES_DATA \
                || ret=1
        fi
    fi
    [ $ret -eq 0 ] && success || failure
    echo
    rm -f $TMP_FILE
    return $ret
}

status() {
    tables=`cat $PROC_IPTABLES_NAMES 2>/dev/null`

    # Do not print status if lockfile is missing and iptables modules are not 
    # loaded.
    # Check if iptable module is loaded
    if [ ! -f "$VAR_SUBSYS_IPTABLES" -a -z "$tables" ]; then
        echo $"Firewall is stopped."
        return 1
    fi

    # Check if firewall is configured (has tables)
    if [ ! -e "$PROC_IPTABLES_NAMES" ]; then
        echo $"Firewall is not configured. "
        return 1
    fi
    if [ -z "$tables" ]; then
        echo $"Firewall is not configured. "
        return 1
    fi

    NUM=
    [ "x$IPTABLES_STATUS_NUMERIC" = "xyes" ] && NUM="-n"
    VERBOSE= 
    [ "x$IPTABLES_STATUS_VERBOSE" = "xyes" ] && VERBOSE="--verbose"
    COUNT=
    [ "x$IPTABLES_STATUS_LINENUMBERS" = "xyes" ] && COUNT="--line-numbers"

    for table in $tables; do
        echo $"Table: $table"
        $IPTABLES -t $table --list $NUM $VERBOSE $COUNT && echo
    done

    return 0
}

restart() {
    [ "x$IPTABLES_SAVE_ON_RESTART" = "xyes" ] && save
    stop
    start
}

case "$1" in
    start)
        stop
        start
        RETVAL=$?
        ;;
    stop)
        [ "x$IPTABLES_SAVE_ON_STOP" = "xyes" ] && save
        stop
        RETVAL=$?
        ;;
    restart)
        restart
        RETVAL=$?
        ;;
    condrestart)
        [ -e "$VAR_SUBSYS_IPTABLES" ] && restart
        ;;
    status)
        status
        RETVAL=$?
        ;;
    panic)
        flush_n_delete
        set_policy DROP
        RETVAL=$?
        ;;
    save)
        save
        RETVAL=$?
        ;;
    *)
        echo $"Usage: $0 {start|stop|restart|condrestart|status|panic|save}"
        exit 1
        ;;
esac

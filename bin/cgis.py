device_family_str = {
    'IPN':  '/uapi-cgi/network.cgi',
    'IPAI': '/cgi-bin/admin/network.cgi',
    'IPE':  '/nvc-cgi/admin/debug.cgi'
}

arr_cgi_str = {
    "param": {
        "IPN":  "/uapi-cgi/param.fcgi?action=list",
        "IPAI": "/cgi-bin/operator/param.cgi",
        "IPE":  "/nvc-cgi/admin/param.fcgi?action=list, /nvc-cgi/admin/vca.fcgi?action=list",
    },
    "snapshot": {
        "IPN":  "/nvc-cgi/operator/snapshot.fcgi",
        "IPAI": "/cgi-bin/operator/snapshot.cgi",
        "IPE":  "/nvc-cgi/operator/snapshot.fcgi",
    },
    "countreport": {
        "IPN":  "/cgi-bin/operator/countreport.cgi?reportfmt=csv&from=%s&to=%s&counter=active&sampling=600&order=Ascending&value=diff", 
        "IPAI": "/cgi-bin/operator/countreport.cgi?reportfmt=csv&from=%s&to=%s&counter=active&sampling=600&order=Ascending&value=diff",
        "IPE":  "/cgi-bin/operator/countreport.cgi?reportfmt=csv&from=%s&to=%s&counter=active&sampling=600&order=Ascending&value=diff",
    },
    "heatmap": {
        "IPN":  "/uapi-cgi/reporthm.cgi?reportfmt=csv&from=%s&to=%s&table=3&individual=yes",
        "IPAI": "",
        "IPE":  "",
    }
}

set_datetime_str = {
    "read":{
        "IPN": "/nvc-cgi/admin/param.fcgi?action=list&group=SYSTEM.Datetime",
        "IPAI": "",
        "IPE": "",
    },
    "set_tz":{
        "IPN": [
            "/uapi-cgi/param.fcgi?action=update&group=SYSTEM.Datetime.Tz&name=Hong_Kong",
            "/uapi-cgi/param.fcgi?action=update&group=SYSTEM.Datetime.Tz&posixrule=HKT-8",
            "/nvc-cgi/admin/timezone.cgi?action=set"
        ],
        "IPAI":[],
        "IPE": [],
    },
    "set_datetime": {
        "IPN": "/nvc-cgi/admin/param.fcgi?action=update&group=System.DateTime&datetime=%s", # %(time.strftime("%m%d%H%M%Y.%S"))
        "IPAI": "",
        "IPE": ""
    }
}
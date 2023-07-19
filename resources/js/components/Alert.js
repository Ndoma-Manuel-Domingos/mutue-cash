export function sweetSuccess(params) {
    Swal.fire({
        toast: true,
        icon: "success",
        title: params,
        animation: false,
        position: "top-end",
        showConfirmButton: false,
        timer: 4000
    })
}


export function sweetError(params) {
    Swal.fire({
        toast: true,
        icon: "error",
        title: params,
        animation: false,
        position: "top-end",
        showConfirmButton: false,
        timer: 4000
    })
}
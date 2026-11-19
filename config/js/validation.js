
function validateStudentForm() {

    const form = document.querySelector("form");
    const name = form.querySelector('[name="name"]').value.trim();
    const code = form.querySelector('[name="student_code"]').value.trim();
    const phone = form.querySelector('[name="parent_phone"]').value.trim();
    const address = form.querySelector('[name="address"]').value.trim();

    if (!name || !code || !phone || !address) {
        alert("Please fill in all required fields!");
        return false;
    }

    const phonePattern = /^[0-9+ ]{9,15}$/;

    if (!phonePattern.test(phone)) {
        alert("Please enter a valid parent phone number!");
        return false;
    }

    if (!/^[a-zA-Z\s'-]+$/.test(name)) {
        alert("Student name should contain valid letters!");
        return false;
    }

    return true;
}

function validateBusForm() {

    const form = document.querySelector("form");

    const busNumber = form.querySelector('[name="bus_number"]').value.trim();
    const capacity = Number(form.querySelector('[name="capacity"]').value);
    const model = form.querySelector('[name="model"]').value.trim();

    if (!busNumber || !model || !form.querySelector('[name="capacity"]').value) {
        alert("Please fill in all required fields!");
        return false;
    }

    if (!Number.isInteger(capacity) || capacity <= 0) {
        alert("Bus capacity must be a positive whole number!");
        return false;
    }

    return true;
}

function validateDriverForm() {

    const form = document.querySelector("form");

    const code = form.querySelector('[name="driver_code"]').value.trim();
    const name = form.querySelector('[name="name"]').value.trim();
    const phone = form.querySelector('[name="phone"]').value.trim();
    const license = form.querySelector('[name="license_number"]').value.trim();

    if (!code || !name || !phone || !license) {
        alert("Please fill in all required fields!");
        return false;
    }

    const phonePattern = /^[0-9+ ]{9,15}$/;

    if (!phonePattern.test(phone)) {
        alert("Please enter a valid phone number!");
        return false;
    }

    if (!/^[a-zA-Z\s'-]+$/.test(name)) {
        alert("Please enter a valid driver name!");
        return false;
    }

    return true;
}

function validateRouteForm() {

    const form = document.querySelector("form");

    const routeName = form.querySelector('[name="route_name"]').value.trim();
    const startingPoint = form.querySelector('[name="starting_point"]').value.trim();
    const destination = form.querySelector('[name="destination"]').value.trim();
    const departure = form.querySelector('[name="departure_time"]').value;
    const arrival = form.querySelector('[name="arrival_time"]').value;

    if (!routeName || !startingPoint || !destination || !departure || !arrival) {
        alert("Please fill in all required fields!");
        return false;
    }

    if (startingPoint.toLowerCase() === destination.toLowerCase()) {
        alert("Starting point and destination must be different!");
        return false;
    }

    if (arrival <= departure) {
        alert("Arrival time must be later than departure time!");
        return false;
    }

    return true;
}

function validateRouteForm() {

    const form = document.querySelector("form");

    const routeName = form.querySelector('[name="route_name"]').value.trim();
    const startingPoint = form.querySelector('[name="starting_point"]').value.trim();
    const destination = form.querySelector('[name="destination"]').value.trim();
    const departure = form.querySelector('[name="departure_time"]').value;
    const arrival = form.querySelector('[name="arrival_time"]').value;

    if (!routeName || !startingPoint || !destination || !departure || !arrival) {
        alert("Please fill in all required fields!");
        return false;
    }

    if (startingPoint.toLowerCase() === destination.toLowerCase()) {
        alert("Starting point and destination must be different!");
        return false;
    }

    if (arrival <= departure) {
        alert("Arrival time must be later than departure time!");
        return false;
    }

    return true;
}
function validateAssignmentForm() {
    const form = document.querySelector("form");

    const student = form.querySelector('[name="student_id"]').value;
    const bus = form.querySelector('[name="bus_id"]').value;
    const route = form.querySelector('[name="route_id"]').value;
    const pickup = form.querySelector('[name="pickup_point"]').value.trim();
    const date = form.querySelector('[name="assignment_date"]').value;

    if (!student || !bus || !route || !pickup || !date) {
        alert("Please fill in all required fields!");
        return false;
    }

    return true;
}

export default {
  props: {
    msg: String
  },
  data() {
    return {
      count: 0
    }
  },
  methods: {
    increment() {
      this.count++
    }
  },
  template: `
  <h2>{{ msg || 'пока входные параметры не переданы' }} V1</h2>
  <button @click="increment">{{ count }}</button>
  `
}
